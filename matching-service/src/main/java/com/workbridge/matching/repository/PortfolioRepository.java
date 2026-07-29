package com.workbridge.matching.repository;

import com.workbridge.matching.model.Portfolio;
import org.springframework.data.mongodb.repository.MongoRepository;

public interface PortfolioRepository extends MongoRepository<Portfolio, String> {
    // findAll() is inherited — sufficient for our use case since we score
    // every freelancer's portfolio against the job's required skills.
}
