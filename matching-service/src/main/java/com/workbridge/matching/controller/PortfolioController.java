package com.workbridge.matching.controller;

import com.workbridge.matching.model.Portfolio;
import com.workbridge.matching.repository.PortfolioRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import java.util.Map;
import java.util.Optional;

@RestController
@RequestMapping("/api/portfolios")
public class PortfolioController {

    private final PortfolioRepository portfolioRepository;

    @Autowired
    public PortfolioController(PortfolioRepository portfolioRepository) {
        this.portfolioRepository = portfolioRepository;
    }

    @PutMapping("/me")
    public ResponseEntity<?> updatePortfolio(@RequestBody Portfolio updateRequest, @RequestHeader(value = "X-User-Id", required = false) Integer userId) {
        // Normally the Python backend would verify the JWT and pass the user_id in an internal header.
        // For this microservice architecture, we'll assume the API gateway or backend proxies this request
        // and includes the X-User-Id header.
        if (userId == null) {
            return ResponseEntity.status(401).body(Map.of("error", "Unauthorized: X-User-Id header missing"));
        }

        Optional<Portfolio> existingOpt = portfolioRepository.findByUserId(userId);
        Portfolio portfolio;
        if (existingOpt.isPresent()) {
            portfolio = existingOpt.get();
        } else {
            portfolio = new Portfolio();
            portfolio.setUser_id(userId);
        }

        if (updateRequest.getSkills() != null) {
            portfolio.setSkills(updateRequest.getSkills());
        }
        if (updateRequest.getBio() != null) {
            portfolio.setBio(updateRequest.getBio());
        }
        if (updateRequest.getPast_work() != null) {
            portfolio.setPast_work(updateRequest.getPast_work());
        }

        portfolioRepository.save(portfolio);

        return ResponseEntity.ok(Map.of("message", "Portfolio updated successfully", "portfolio", portfolio));
    }

    @GetMapping("/{userId}")
    public ResponseEntity<?> getPortfolio(@PathVariable Integer userId) {
        Optional<Portfolio> existingOpt = portfolioRepository.findByUserId(userId);
        if (existingOpt.isPresent()) {
            return ResponseEntity.ok(existingOpt.get());
        } else {
            return ResponseEntity.status(404).body(Map.of("error", "Portfolio not found"));
        }
    }
}
