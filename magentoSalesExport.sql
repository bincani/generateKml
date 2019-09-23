SELECT
    sfo.entity_id,
    sfo.increment_id,
    sfoa.street,
    sfoa.city,
    sfoa.postcode,
    sfoa.region,
    sfoa.country_id
from sales_flat_order sfo
left join sales_flat_order_address sfoa on sfo.entity_id = sfoa.parent_id AND sfoa.address_type = 'shipping'
where sfoa.country_id = 'AU' and sfoa.region = "New South Wales"
AND
convert_tz(sfo.created_at, '+00:00', '+10:00') >= '2018-07-01 00:00:00'
AND
convert_tz(sfo.created_at, '+00:00', '+10:00') <= '2019-06-30 23:59:59'
limit 10;
