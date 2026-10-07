package com.workbridge.matching.service;

import com.workbridge.matching.model.MatchResult;
import com.workbridge.matching.model.Portfolio;
import org.junit.jupiter.api.Test;

import java.util.Arrays;
import java.util.Collections;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;

class SkillMatchServiceTest {

    private Portfolio makePortfolio(Integer userId, String... skills) {
        Portfolio p = new Portfolio();
        p.setUser_id(userId);
        p.setSkills(Arrays.asList(skills));
        return p;
    }

    @Test
    void fullMatchScores100Percent() {
        Portfolio freelancer = makePortfolio(1, "Python", "Flask", "MongoDB");
        List<String> required = Arrays.asList("Python", "Flask");

        MatchResult result = SkillMatchService.scorePortfolio(required, freelancer);

        assertEquals(2, result.getOverlapCount());
        assertEquals(100.0, result.getMatchPercentage());
        assertTrue(result.getMatchedSkills().containsAll(Arrays.asList("python", "flask")));
    }

    @Test
    void partialMatchScoresProportionally() {
        Portfolio freelancer = makePortfolio(2, "Python", "Django");
        List<String> required = Arrays.asList("Python", "Flask", "MongoDB", "React");

        MatchResult result = SkillMatchService.scorePortfolio(required, freelancer);

        assertEquals(1, result.getOverlapCount());
        assertEquals(25.0, result.getMatchPercentage()); // 1 of 4 required skills = 25%
    }

    @Test
    void noOverlapScoresZero() {
        Portfolio freelancer = makePortfolio(3, "Graphic Design", "Illustrator");
        List<String> required = Arrays.asList("Python", "Java");

        MatchResult result = SkillMatchService.scorePortfolio(required, freelancer);

        assertEquals(0, result.getOverlapCount());
        assertEquals(0.0, result.getMatchPercentage());
        assertTrue(result.getMatchedSkills().isEmpty());
    }

    @Test
    void matchingIsCaseInsensitive() {
        Portfolio freelancer = makePortfolio(4, "PYTHON", "react");
        List<String> required = Arrays.asList("python", "REACT");

        MatchResult result = SkillMatchService.scorePortfolio(required, freelancer);

        assertEquals(2, result.getOverlapCount());
        assertEquals(100.0, result.getMatchPercentage());
    }

    @Test
    void handlesWhitespaceAroundSkillNames() {
        Portfolio freelancer = makePortfolio(5, "  Python ", "React ");
        List<String> required = Arrays.asList(" python", "react  ");

        MatchResult result = SkillMatchService.scorePortfolio(required, freelancer);

        assertEquals(2, result.getOverlapCount());
    }

    @Test
    void emptyRequiredSkillsReturnsZeroScore() {
        Portfolio freelancer = makePortfolio(6, "Python", "React");

        MatchResult result = SkillMatchService.scorePortfolio(Collections.emptyList(), freelancer);

        assertEquals(0, result.getOverlapCount());
        assertEquals(0.0, result.getMatchPercentage());
    }

    @Test
    void freelancerWithNullSkillsHandledGracefully() {
        Portfolio freelancer = new Portfolio();
        freelancer.setUser_id(7);
        freelancer.setSkills(null);

        MatchResult result = SkillMatchService.scorePortfolio(Arrays.asList("Python"), freelancer);

        assertEquals(0, result.getOverlapCount());
        assertNotNull(result.getMatchedSkills());
    }
}
