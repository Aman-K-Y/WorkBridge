package com.workbridge.matching;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

/**
 * WorkBridge - Matching Microservice
 * A standalone Spring Boot service that recommends freelancers to clients
 * based on skill overlap with a job's required skills.
 *
 * Deliberately isolated from the main Python backend to demonstrate a
 * simple microservice architecture: the Python API orchestrates requests,
 * and this Java service handles the matching computation independently.
 */
@SpringBootApplication
public class MatchingApplication {
    public static void main(String[] args) {
        SpringApplication.run(MatchingApplication.class, args);
    }
}
