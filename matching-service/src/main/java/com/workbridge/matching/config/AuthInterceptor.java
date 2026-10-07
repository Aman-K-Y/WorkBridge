package com.workbridge.matching.config;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.stereotype.Component;
import org.springframework.web.servlet.HandlerInterceptor;

@Component
public class AuthInterceptor implements HandlerInterceptor {

    @Override
    public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws Exception {
        String uri = request.getRequestURI();
        
        // Only intercept /api/portfolios/me requests for updating
        if (uri.equals("/api/portfolios/me") && request.getMethod().equalsIgnoreCase("PUT")) {
            String role = request.getHeader("X-User-Role");
            if (role == null || !role.equalsIgnoreCase("freelancer")) {
                response.setStatus(HttpServletResponse.SC_FORBIDDEN);
                response.setContentType("application/json");
                response.getWriter().write("{\"error\": \"Forbidden: Only freelancers can update portfolios\"}");
                return false;
            }
        }

        return true;
    }
}
