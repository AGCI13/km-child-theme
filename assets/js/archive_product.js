jQuery(document).ready(() => {

    if (jQuery(window).width() < 767) {

        let widget_archive_product = document.getElementById('widget_archive_product');
        let category_tree = document.getElementById('category_tree');
        widget_archive_product.before(category_tree);
        let sub_categories = document.getElementsByClassName('wct-sub-categories wct--visible');
        if (sub_categories.length === 1) {
            let href_subcat_parent = sub_categories[0].parentNode.children[0].href;
            let div = document.createElement("div");
            div.classList.add('wct-sub-category');
            let a = document.createElement("a");
            a.href = href_subcat_parent;
            a.textContent = 'Voir tout';
            div.appendChild(a);
            sub_categories[0].children[0].before(div);
            let dots = document.createElement("div");
            dots.classList.add('dots');
            let children = sub_categories[0].childNodes;
            children.forEach(function (item) {
                let dot = document.createElement("div");
                dot.classList.add('dot');
                if (item.classList.contains('wct--active')) {
                    dot.classList.add('selected');
                }
                dots.appendChild(dot)
            });
            category_tree.after(dots);
            let label = document.createElement("div");
            label.classList.add('label_cat');
            let span = document.createElement("span");
            let vowel = document.getElementById('cat_vowel').value;
            if (vowel === '0') {
                span.textContent = 'Nos ' + sub_categories[0].childElementCount + ' autres types de ' + document.getElementById('cat_parent_name').value + ' :';
            } else {
                span.textContent = 'Nos ' + sub_categories[0].childElementCount + ' autres types d’' + document.getElementById('cat_parent_name').value + ' :';
            }
            label.appendChild(span);
            category_tree.before(label);

        } else {
            let category = document.getElementsByClassName('wct-category wct--active')[0];
            category.classList.add('wct--visible');
            let href_cat_parent = category.children[0].href;
            let div = document.createElement("div");
            div.classList.add('wct-sub-category');
            div.classList.add('wct--active');
            let a = document.createElement("a");
            a.href = href_cat_parent;
            a.textContent = 'Voir tout';
            div.appendChild(a);
            category.children[2].children[0].before(div);
            category.children[2].classList.add('wct--visible');
            let sub_categories = document.getElementsByClassName('wct-sub-categories wct--visible');
            let children = sub_categories[0].childNodes;
            let dots = document.createElement("div");
            dots.classList.add('dots');
            children.forEach(function (item) {
                let dot = document.createElement("div");
                dot.classList.add('dot');
                if (item.classList.contains('wct--active')) {
                    dot.classList.add('selected');
                }
                dots.appendChild(dot)
            });
            category_tree.after(dots);
            let label = document.createElement("div");
            label.classList.add('label_cat');
            let span = document.createElement("span");
            let vowel = document.getElementById('cat_vowel').value;
            if (vowel === '0') {
                span.textContent = 'Nos types de ' + document.getElementById('cat_parent_name').value + ' :';
            } else {
                span.textContent = 'Nos types d’' + document.getElementById('cat_parent_name').value + ' :';
            }
            label.appendChild(span);
            category_tree.before(label);
        }
    }
});