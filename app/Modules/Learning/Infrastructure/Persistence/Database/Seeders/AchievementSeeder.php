<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Seeders;

use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['streak_3', '3 дня подряд', 'Серия', 'Учи 3 дня без пропуска', 'common', '#ff9d5c', 50, 'streak_days', 3, null, 10],
            ['words_50', '50 слов', 'Первый словарь', 'Выучи 50 слов', 'common', '#5AD4B5', 100, 'words_learned', 50, null, 20],
            ['first_lesson', 'Первый урок', 'Старт дан', 'Пройди первый урок', 'common', '#5B74FF', 25, 'reviews_total', 1, null, 30],
            ['goal_streak_5', 'Цель 5 дней', 'Неделя фокуса', 'Достигай цель 5 дней', 'rare', '#F5C16A', 150, 'streak_days', 5, null, 40],
            ['words_100', '100 слов', 'Словарь растёт', 'Выучи 100 слов', 'rare', '#F08AB4', 200, 'words_learned', 100, null, 50],
            ['words_500', 'Полиглот', '500 слов', 'Выучи 500 слов', 'epic', '#a78bfa', 1000, 'words_learned', 500, null, 60],
            ['phrases_50', 'Болтун', '50 фраз', 'Выучи 50 фраз', 'rare', '#5B74FF', 120, 'reviews_of_type', 50, ['learnable_type' => 'phrase'], 70],
            ['streak_7', 'Спринт 7', '7 дней подряд', 'Серия 7 дней', 'rare', '#ff9d5c', 200, 'streak_days', 7, null, 80],
            ['accuracy_95', 'Отличник', '95% точность', 'Точность от 95% на 20 словах', 'epic', '#F5C16A', 300, 'accuracy', 95, ['min_reviews' => 20], 90],
        ];

        foreach ($rows as [$code, $title, $desc, $condition, $rarity, $color, $xp, $rule, $target, $extra, $sort]) {
            Achievement::query()->updateOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    'desc' => $desc,
                    'condition' => $condition,
                    'rarity' => $rarity,
                    'color' => $color,
                    'reward_xp' => $xp,
                    'rule_type' => $rule,
                    'rule_target' => $target,
                    'rule_extra' => $extra,
                    'sort' => $sort,
                ],
            );
        }
    }
}
