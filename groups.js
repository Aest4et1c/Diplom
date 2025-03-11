function toggle_group(self, id) {
	let sub = $(`tr#group-${id}-sub`);
	if (sub.is(":visible")) {
		sub.hide();
	} else {
		sub.show();
	}
}

let child = {
	id: undefined
};

const grades = [
	'Плохо',
	'Удовлетворительно',
	'Хорошо',
	'Отлично'
];

function show_child_grades(self, id) {
	if (child.id === id) return;
	$(`div#child-grades`).show();
	if (child.id !== undefined) {
		$(`div#child-grades table tbody tr`).not('#child-grades-filter').remove();
	}
	child.id = id;

	$.post(
		window.location.href,
		{
			event: 'onChildSelected',
			id: id
		},
		function(data) {
			let response = JSON.parse(data);
			$(`div#child-grades h1#child-name`).html(response['child']['name']);
			for (let i = 0; i < response['grades'].length; i++) {
				let grade = response['grades'][i];
				$(`
				<tr>
					<td>${grades[grade['social']]}</td>
					<td>${grades[grade['speech']]}</td>
					<td>${grades[grade['educational']]}</td>
					<td>${grades[grade['artistic']]}</td>
					<td>${grades[grade['physical']]}</td>
					<td>${grade['date']}</td>
				</tr>
				`).prependTo('div#child-grades table tbody');
			}
		}
	);
}