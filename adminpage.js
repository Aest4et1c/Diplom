
function on_change_hash(hash_from, hash_to) {
	let eto = $('div.tabinfo.content span' + hash_to);
	let efrom;
	if (hash_from !== undefined) efrom = $('div.tabinfo.content span' + hash_from);
	if (efrom === undefined || efrom.length == 0) {
		eto.show();
	} else {
		efrom.hide();
		eto.show();
	}
}

let user_editor = {
	current: undefined,
	button: undefined
};

function edit_user(self, id, ignore) {
	let tds = $('table#users-table tr#user-' + String(id) + ' td');
	if (id === user_editor.current) {
		tds.prop(
			'contenteditable',
			false
		);
		user_editor.button = undefined;
		user_editor.current = undefined;
		self.value = 'Редактировать';

		let params = {
			event: 'onUserEdit',
			id: id
		};
		tds.each(
			function() {
				if (this.id !== '') {
					params[this.id] = this.innerText;
				}
			}
		);
		if (ignore !== true) {
			$.post(
				window.location.href,
				params
			);
		}
	} else {
		if (user_editor.current !== undefined) {
			edit_user(user_editor.button, user_editor.current);
		}

		user_editor.button = self;
		user_editor.current = id;
		tds.prop(
			'contenteditable',
			true
		);
		self.value = 'Применить';
	}
}

function remove_user(self, id) {
	if (user_editor.current !== undefined) {
		edit_user(user_editor.button, user_editor.current, true);
	}
	$('table#users-table tr#user-' + String(id)).remove();
	$.post(
		window.location.href,
		{
			event: 'onUserRemove',
			id: id
		}
	);
}

async function add_user(self) {
	let tds = $('table#users-table tr#user-new td *');

	let params = {
		event: 'onUserAdd'
	};
	tds.each(
		function() {
			if ((this.id !== '' && this.tagName === 'INPUT') || this.tagName === 'OPTION') {
				let id = this.id;
				if (id === '') id = this.parentElement.id;
				params[id] = this.value;
			}
		}
	);

	await $.post(
		window.location.href,
		params,
		function(data) {
			tds.each(
				function() {
					if (this.id !== '' && this.tagName === 'INPUT') {
						this.value = '';
					}
				}
			);
			window.location.reload();
		}
	);
}

let group_editor = {
	current: undefined,
	button: undefined
};
function toggle_group(self, id) {
	if (group_editor.current === undefined) {
		let sub = $(`tr#group-${id}-sub`);
		if (sub.is(":visible")) {
			sub.hide();
		} else {
			sub.show();
		}
	}
}

function edit_group(self, id, ignore) {
	let tds = $('table#groups-table tr#group-' + String(id) + ' td');
	if (id === group_editor.current) {
		tds.prop(
			'contenteditable',
			false
		);
		group_editor.button = undefined;
		group_editor.current = undefined;
		self.value = 'Редактировать';

		let params = {
			event: 'onGroupEdit',
			id: id
		};
		tds.each(
			function() {
				if (this.id !== '') {
					params[this.id] = this.innerText;
				}
			}
		);
		if (ignore !== true) {
			$.post(
				window.location.href,
				params
			);
		}
	} else {
		if (group_editor.current !== undefined) {
			edit_group(group_editor.button, group_editor.current);
		}

		group_editor.button = self;
		group_editor.current = id;
		tds.prop(
			'contenteditable',
			true
		);
		self.value = 'Применить';
	}
}

async function add_child(self, group_id) {
	let tds = $(`tr#group-${group_id}-child-new td *`).filter('input, select');
	let params = {
		event: 'onChildAdd',
		group: group_id
	};
	tds.each(
		function() {
			if ((this.id !== '' && this.tagName === 'INPUT') || this.tagName === 'OPTION') {
				let id = this.id;
				if (id === '') id = this.parentElement.id;
				params[id] = this.value;
			}
		}
	);
	await $.post(
		window.location.href,
		params,
		function(data) {
			tds.each(
				function() {
					if (this.id !== '' && this.tagName === 'INPUT') {
						this.value = '';
					}
				}
			);
			window.location.reload();
		}
	);
}

let child_editor = {
	current: undefined,
	button: undefined
};
async function edit_child(self, id, ignore) {
	let tds = $('tr#child-' + String(id) + ' td');
	if (id === child_editor.current) {
		tds.prop(
			'contenteditable',
			false
		);
		child_editor.button = undefined;
		child_editor.current = undefined;
		self.value = 'Редактировать';

		let params = {
			event: 'onChildEdit',
			id: id
		};
		tds.each(
			function() {
				if (this.id !== '') {
					params[this.id] = this.innerText;
				}
			}
		);
		if (ignore !== true) {
			$.post(
				window.location.href,
				params
			);
		}
	} else {
		if (child_editor.current !== undefined) {
			edit_group(child_editor.button, child_editor.current);
		}

		child_editor.button = self;
		child_editor.current = id;
		tds.prop(
			'contenteditable',
			true
		);
		self.value = 'Применить';
	}
}

async function remove_child(self, id) {
	let params = {
		event: 'onChildRemove',
		id: id
	};
	let response = { };
	await $.post(
		window.location.href,
		params,
		function(data) {
			response = JSON.parse(data);
			if ('error' in response)
			{
				alert(response['error']);
				return;
			}
			$(`tr#child-${id}`).remove();
		}
	);
}

async function add_group(self) {
	let tds = $('tr#group-new td *').filter('input, select');
	let params = {
		event: 'onGroupAdd'
	};
	tds.each(
		function() {
			if ((this.id !== '' && this.tagName === 'INPUT') || this.tagName === 'OPTION') {
				let id = this.id;
				if (id === '') id = this.parentElement.id;
				params[id] = this.value;
			}
		}
	);
	await $.post(
		window.location.href,
		params,
		function(data) {
			tds.each(
				function() {
					if (this.id !== '' && this.tagName === 'INPUT') {
						this.value = '';
					}
				}
			);
			window.location.reload();
		}
	);
}

async function remove_group(self, id) {
	let params = {
		event: 'onGroupRemove',
		id: id
	};
	let response = { };
	await $.post(
		window.location.href,
		params,
		function(data) {
			response = JSON.parse(data);
			if ('error' in response)
			{
				alert(response['error']);
				return;
			}
			$(`tr#group-${id}`).remove();
		}
	);
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

function parse_score(self) {
	if (grades.indexOf(self.value) >= 0) {
		return;
	}
	if (isNaN(self.value)) {
		self.value = 0;
	} else {
		let value = parseInt(self.value);
		if (value < 0) value = 0;
		if (value >= grades.length) value = grades.length - 1;
		self.value = grades[value];
	}
	parse_score(self);
}

function unparse_score(score) {
	const index = grades.indexOf(score);
	if (index >= 0) {
		return index;
	}
	return 0;
}

function show_child_grades(self, id) {
	if (child.id === id) return;
	$(`div#child-grades`).show();
	if (child.id !== undefined) {
		$(`div#child-grades table tbody tr`).not('#child-grades-filter').not('#child-grade-new').remove();
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
				<tr id="grade-${grade['id']}">
					<td>${grades[grade['social']]}</td>
					<td>${grades[grade['speech']]}</td>
					<td>${grades[grade['educational']]}</td>
					<td>${grades[grade['artistic']]}</td>
					<td>${grades[grade['physical']]}</td>
					<td>${grade['date']}</td>
					<td><input type="button" value="Удалить" onclick="remove_grade(this, ${grade['id']})"></td>
				</tr>
				`).prependTo('div#child-grades table tbody');
			}
		}
	);
}

function remove_grade(self, id) {
	$.post(
		window.location.href,
		{
			event: 'onGradeRemove',
			id: id
		},
		function(data) {
			let response = JSON.parse(data);
			if ('error' in response) {
				alert(response['error']);
			} else {
				$(`tr#grade-${id}`).remove();
			}
		}
	);
}
function add_grade(self) {
	let tds = $('tr#child-grade-new td *').filter('input, select');
	let params = {
		event: 'onGradeAdd',
		child: child.id
	};
	tds.each(
		function() {
			if (this.id !== '') {
				if (this.type === 'text') {
					params[this.id] = unparse_score(this.value);
				} else if (this.type === 'date') {
					params[this.id] = Date.parse(this.value) / 1000;
				}
				
			}
		}
	);
	$.post(
		window.location.href,
		params,
		function(data) {
			let response = JSON.parse(data);
			if ('error' in response) {
				alert(response['error']);
			} else {
				window.location.reload();
			}
		}
	);
}

document.addEventListener(
	'DOMContentLoaded',
	function() {
		$('div.tabinfo.content span').hide();

		let h = window.location.hash;
		if (h === '') h = '#users';
		$(window).on(
			'hashchange',
			function() {
				if (h !== window.location.hash) {
					on_change_hash(h, window.location.hash);
					h = window.location.hash;
				}
			}
		);
		on_change_hash(undefined, h);
	}
);
