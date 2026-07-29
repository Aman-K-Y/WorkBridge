package com.workbridge.matching.service;

import com.workbridge.matching.model.MatchResult;
import com.workbridge.matching.model.Portfolio;
import com.workbridge.matching.repository.PortfolioRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.util.*;
import java.util.stream.Collectors;

/**
 * Computes how well each freelancer's skills match a job's required skills.
 *
 * The scoring logic (scorePortfolio) is a pure, static method with no
 * Spring or database dependencies — this makes it independently unit
 * testable, and keeps the "business logic" cleanly separated from the
 * data access layer (PortfolioRepository).
 */
@Service
public class SkillMatchService {

    private final PortfolioRepository portfolioRepository;

    @Autowired
    public SkillMatchService(PortfolioRepository portfolioRepository) {
        this.portfolioRepository = portfolioRepository;
    }

    /**
     * Finds and ranks freelancers by skill overlap with the given required skills.
     * Only freelancers with at least one matching skill are returned.
     */
    public List<MatchResult> findMatches(List<String> requiredSkills, int limit) {
        List<Portfolio> allPortfolios = portfolioRepository.findAll();

        List<MatchResult> results = allPortfolios.stream()
                .map(portfolio -> scorePortfolio(requiredSkills, portfolio))
                .filter(result -> result.getOverlapCount() > 0)
                .sorted(Comparator.comparingDouble(MatchResult::getMatchPercentage).reversed())
                .limit(limit)
                .collect(Collectors.toList());

        return results;
    }

    /**
     * Pure scoring function: compares a job's required skills against one
     * freelancer's portfolio skills, case-insensitively, and returns a
     * MatchResult with the overlap count and match percentage.
     *
     * matchPercentage is calculated relative to the job's required skill
     * count, so a freelancer who covers all required skills scores 100%
     * even if they also have unrelated skills the job didn't ask for.
     */
    public static MatchResult scorePortfolio(List<String> requiredSkills, Portfolio portfolio) {
        if (requiredSkills == null || requiredSkills.isEmpty() || portfolio.getSkills() == null) {
            return new MatchResult(portfolio.getUser_id(), Collections.emptyList(), 0, 0.0);
        }

        Set<String> normalizedRequired = requiredSkills.stream()
                .map(s -> s.trim().toLowerCase())
                .collect(Collectors.toCollection(LinkedHashSet::new));

        Set<String> normalizedFreelancerSkills = portfolio.getSkills().stream()
                .map(s -> s.trim().toLowerCase())
                .collect(Collectors.toSet());

        List<String> matched = normalizedRequired.stream()
                .filter(normalizedFreelancerSkills::contains)
                .collect(Collectors.toList());

        int overlapCount = matched.size();
        double matchPercentage = normalizedRequired.isEmpty()
                ? 0.0
                : Math.round((overlapCount / (double) normalizedRequired.size()) * 1000.0) / 10.0; // one decimal place

        return new MatchResult(portfolio.getUser_id(), matched, overlapCount, matchPercentage);
    }
}
