namespace :generate do
  task :migration, :name do |t, args|
    filename = "migrations/#{Time.now.to_i}_#{args.name}.php"
    puts filename
    `cp tourniquet_engine/migrations/migration.template.php #{filename}`
  end
end
