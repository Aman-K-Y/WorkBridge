package com.workbridge.matching.model;

import org.springframework.data.annotation.Id;
import org.springframework.data.mongodb.core.mapping.Document;

import java.util.List;

/**
 * Maps to documents in the 'portfolios' MongoDB collection
 * (the same collection the Python backend and mongo/init_collections.py write to).
 */
@Document(collection = "portfolios")
public class Portfolio {

    @Id
    private String id;

    private Integer user_id;
    private List<String> skills;
    private String bio;
    private Object past_work;

    public Portfolio() {
    }

    public String getId() {
        return id;
    }

    public void setId(String id) {
        this.id = id;
    }

    public Integer getUser_id() {
        return user_id;
    }

    public void setUser_id(Integer user_id) {
        this.user_id = user_id;
    }

    public List<String> getSkills() {
        return skills;
    }

    public void setSkills(List<String> skills) {
        this.skills = skills;
    }

    public String getBio() {
        return bio;
    }

    public void setBio(String bio) {
        this.bio = bio;
    }

    public Object getPast_work() {
        return past_work;
    }

    public void setPast_work(Object past_work) {
        this.past_work = past_work;
    }
}
