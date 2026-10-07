package com.workbridge.matching.repository;

import com.workbridge.matching.model.Portfolio;
import org.springframework.data.mongodb.repository.MongoRepository;

import org.springframework.data.mongodb.repository.Query;
import java.util.Optional;

public interface PortfolioRepository extends MongoRepository<Portfolio, String> {
    
    @Query("{ 'user_id' : ?0 }")
    Optional<Portfolio> findByUserId(Integer userId);
}
