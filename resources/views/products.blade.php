<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
</head>
<body>

<h1>Lista Prodotti</h1>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Titolo</th>
        <th>Prezzo</th>
        <th>Categoria</th>
    </tr>

    @foreach($products as $product)
    <tr>
        <td>{{ $product->id }}</td>
        <td>{{ $product->title }}</td>
        <td>{{ $product->price }}</td>
        <td>{{ $product->category }}</td>
    </tr>
    @endforeach

</table>

</body>
</html>