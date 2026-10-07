# ============================================
# WorkBridge - Matching Service Dockerfile
# Multi-stage build: compiles with Maven, then runs on a lean JRE image.
# Render (and most container platforms) auto-detect this file and build from it.
# ============================================

# --- Stage 1: Build the jar with Maven ---
FROM maven:3.9-eclipse-temurin-21 AS build
WORKDIR /app
COPY pom.xml .
# Download dependencies first so Docker can cache this layer between builds
RUN mvn dependency:go-offline -B
COPY src ./src
RUN mvn package -DskipTests -B

# --- Stage 2: Run it on a lightweight JRE-only image ---
FROM eclipse-temurin:21-jre-alpine
WORKDIR /app
COPY --from=build /app/target/*.jar app.jar

# Render provides the PORT environment variable at runtime; application.properties
# is configured to read it via ${PORT:8081}, defaulting to 8081 for local runs.
EXPOSE 8081
ENTRYPOINT ["java", "-jar", "app.jar"]
