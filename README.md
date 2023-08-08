# AI Tutor

## Description:

Welcome to the AI-Enhanced Engineering Learning Platform repository. This project represents a groundbreaking integration of artificial intelligence to revolutionize engineering education. Leveraging the capabilities of leading AI models like OpenAI, we've created a dynamic, personalized learning experience that adapts to individual needs and industry requirements.

## Key Features:

- Personalized Learning Paths: Customizable curriculums tailored to learners' unique abilities, preferences, and pace.
- Dynamic Question Generation: AI-driven, industry-specific question creation across multiple difficulty levels.
- Real-time Feedback and Hints: Instant evaluation and actionable hints to facilitate continuous learning.
- Adaptive Learning Environment: Continual assessment and adjustment to keep learners challenged and engaged.
- Collaboration with Industry Experts: Insights from real-world experts to align learning with industry needs.
- Robust Analytics and Insights: In-depth analytics to track progress, strengths, weaknesses, and areas for improvement.

## Configuration

### Install Dependencies

In the terminal

Node:
```
npm install
```

Composer
```
composer install
```

### Database

Configure your database:

in env
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_tutor
DB_USERNAME=root
DB_PASSWORD=admin123
```

In the terminal run migrations:
```
php artisan migrate
```

### AI Configuration

Generate an [OPEN AI api key](https://platform.openai.com/account/api-keys).

in env:
```
OPENAI_API_KEY=<your-openai-api-key>
```

## Getting Started:

### Serve the site

```
php artisan serve
```

### Create a user

Visit [http://127.0.0.1:8000/register](http://127.0.0.1:8000/register) to create your user.

Get your token in the terminal

```
php artisan tinker
$user = App\Models\User::first();
$token = $user->createToken('<my-token-name>')->plainTextToken;
```

Then copy the given output (example)

```
> $user->createToken('myToken')->plainTextToken;
= "1|hM0uwjS3I2nybMPRIKCtu77JU5UZdtD4n0WCEjOU"
```

Make sure you save the token somewhere secure otherwise you'll have
to generate another one.

### API Authentication

Note all api requests require a user personal access token, like the one generated above
set as the `Authentication: Bearer <token>` request header.

### Api/Question

Generate new questions in the api by requesting the following api endpoint

```
/api/question?topic_id=<id>&difficulty_level=<num:1..10>
```

Response Example:

```
{
    question: {
        id: 1,
        question: 'write a php variable named duck with the value 1',
        difficulty_level: 1,
        topic_id: <topic_id>,
        created_at: 'UTC-date',
        updated_at: 'UTC-date'
    }
}
```


## Testing


Unit tests
```
php artisan test --testsuite=Unit 
```

Feature tests
```
php artisan test --testsuite=Feature
```

Specific test file
```
php artisan test Tests\<path-to-test-file>Test.php
```

All tests
```
php artisan test
```


## Contribution:

...TODO