package com.workbridge.matching.model;

import java.util.List;

/**
 * Represents one freelancer's match score against a job's required skills.
 */
public class MatchResult {

    private Integer userId;
    private List<String> matchedSkills;
    private int overlapCount;
    private double matchPercentage;

    public MatchResult() {
    }

    public MatchResult(Integer userId, List<String> matchedSkills, int overlapCount, double matchPercentage) {
        this.userId = userId;
        this.matchedSkills = matchedSkills;
        this.overlapCount = overlapCount;
        this.matchPercentage = matchPercentage;
    }

    public Integer getUserId() {
        return userId;
    }

    public void setUserId(Integer userId) {
        this.userId = userId;
    }

    public List<String> getMatchedSkills() {
        return matchedSkills;
    }

    public void setMatchedSkills(List<String> matchedSkills) {
        this.matchedSkills = matchedSkills;
    }

    public int getOverlapCount() {
        return overlapCount;
    }

    public void setOverlapCount(int overlapCount) {
        this.overlapCount = overlapCount;
    }

    public double getMatchPercentage() {
        return matchPercentage;
    }

    public void setMatchPercentage(double matchPercentage) {
        this.matchPercentage = matchPercentage;
    }
}
