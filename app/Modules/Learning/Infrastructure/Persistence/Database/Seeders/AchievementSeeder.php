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
            ['words_200', '200 слов', 'Растущий словарь', 'Выучи 200 слов', 'rare', '#5AD4B5', 350, 'words_learned', 200, null, 95],
            ['reviews_100', '100 повторений', 'Разминка', 'Сделай 100 повторений', 'common', '#5B74FF', 120, 'reviews_total', 100, null, 100],
            ['accuracy_90', 'Меткий', '90% точность', 'Точность от 90% на 50 словах', 'rare', '#F08AB4', 250, 'accuracy', 90, ['min_reviews' => 50], 105],
            ['own_words_30', 'Свои слова', '30 своих слов', 'Выучи 30 своих слов', 'rare', '#5AD4B5', 250, 'reviews_of_type', 30, ['learnable_type' => 'user_entry'], 108],
            ['goals_5', 'Целеустремлённый', '5 целей', 'Закрой 5 целей', 'rare', '#F5C16A', 300, 'goals_completed', 5, null, 110],
            ['streak_14', 'Две недели', '14 дней подряд', 'Серия 14 дней', 'epic', '#ff9d5c', 500, 'streak_days', 14, null, 115],
            ['reviews_500', 'Марафонец', '500 повторений', 'Сделай 500 повторений', 'epic', '#a78bfa', 600, 'reviews_total', 500, null, 120],
            ['goals_20', 'Достигатор', '20 целей', 'Закрой 20 целей', 'epic', '#F5C16A', 700, 'goals_completed', 20, null, 125],
            ['xp_5000', 'Опытный', '5000 XP', 'Набери 5000 XP', 'epic', '#5B74FF', 800, 'xp_total', 5000, null, 130],
            ['accuracy_98', 'Перфекционист', '98% точность', 'Точность от 98% на 100 словах', 'legendary', '#f43f5e', 1000, 'accuracy', 98, ['min_reviews' => 100], 135],
            ['words_1000', 'Тысячник', '1000 слов', 'Выучи 1000 слов', 'legendary', '#a78bfa', 2500, 'words_learned', 1000, null, 140],
            ['streak_30', 'Месяц', '30 дней подряд', 'Серия 30 дней', 'legendary', '#ff9d5c', 1200, 'streak_days', 30, null, 145],
            ['reviews_2000', 'Неутомимый', '2000 повторений', 'Сделай 2000 повторений', 'legendary', '#5AD4B5', 1500, 'reviews_total', 2000, null, 150],
            ['xp_20000', 'Мастер', '20000 XP', 'Набери 20000 XP', 'legendary', '#F08AB4', 2000, 'xp_total', 20000, null, 155],
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
