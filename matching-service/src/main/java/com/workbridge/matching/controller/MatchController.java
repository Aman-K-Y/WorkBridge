package com.workbridge.matching.controller;

import com.workbridge.matching.model.MatchResult;
import com.workbridge.matching.service.SkillMatchService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.util.Arrays;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@RestController
public class MatchController {

    private final SkillMatchService skillMatchService;

    @Autowired
    public MatchController(SkillMatchService skillMatchService) {
        this.skillMatchService = skillMatchService;
    }

    /**
     * GET /api/match?skills=Python,React,MongoDB&limit=5
     * Returns freelancers ranked by how many of the given skills they have,
     * as a percentage of the skills requested.
     */
    @GetMapping("/api/match")
    public Map<String, Object> matchFreelancers(
            @RequestParam String skills,
            @RequestParam(defaultValue = "5") int limit
    ) {
        if (limit < 1 || limit > 50) {
            throw new IllegalArgumentException("Limit must be between 1 and 50");
        }

        List<String> skillList = Arrays.stream(skills.split(","))
                .map(String::trim)
                .filter(s -> !s.isEmpty())
                .collect(Collectors.toList());

        List<MatchResult> matches = skillMatchService.findMatches(skillList, limit);

        return Map.of(
                "requiredSkills", skillList,
                "totalMatches", matches.size(),
                "matches", matches
        );
    }

    @GetMapping("/health")
    public Map<String, String> health() {
        return Map.of("status", "Matching service is running");
    }
}
