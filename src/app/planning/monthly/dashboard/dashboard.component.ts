import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  current_date;
  current_month;
  month;
  last_date;

  products;
  selectedProduct = [];
  batches;
  selectedResult = [];

  materials = [];

  productList = [];

  raw_materials = [];

  uncommons = [];
  commons = [];
  constructor(private service: DataAccessService, private datePipe:DatePipe) {
    this.current_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.current_month=this.datePipe.transform(Date.now(),'MM-yyyy');
    this.month=this.datePipe.transform(Date.now(),'MMM');
    this.last_date = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
  }

  ngOnInit() {
  }

  getProducts(product_type) {
    this.service.get('production/lot/plan.php?type=getProducts&product_type='+product_type).subscribe(response => {
      this.products = response;
    });
  }

  getProductDetails(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];
    this.batches = this.selectedProduct['batches']; 
  }

  getBatchDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.batches[index];
    } else {
      this.batches = [];
    }
  }

  addProduct(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['product_name'] = this.selectedProduct['product_name'];
    temp['raw_materials'] = this.selectedResult['raw_materials'];
    temp['packing_materials'] = this.selectedResult['packing_materials'];

    let raw_materials = temp['raw_materials'];
    for (let i = 0; i < raw_materials.length; i++) {
      let raw_material = raw_materials[i];

      let flag = 0;
      for (let j = 0; j < this.raw_materials.length; j++) {
        let material = this.raw_materials[j];
        if (material['material_code'] == raw_material['material_code']) {
          flag = 1;
          break;
        }
      }
      if (flag == 0) {
        raw_material['product_code'] = temp['product_code'];
        raw_material['batch_size'] = temp['batch_size'];
        this.raw_materials[this.raw_materials.length] = raw_material;
      }
    }

    this.productList[this.productList.length] = temp;
    this.getMaterialDetails();
  }

  getMaterialDetails() {
    this.service.post('planning/raw.php?type=getMaterialDetails', JSON.stringify(this.raw_materials)).subscribe((response: any) => {
      this.raw_materials = response;

      /* for (let i = 0; i < this.raw_materials.length; i++) {
        let material = this.raw_materials[i];

        let products = this.productList;
        for (let j = 0; j < products.length; j++) {
          let product = products[j];
          let materials = product['raw_materials'];
          for (let k = 0; k < materials.length; k++) {
            let temp = materials[k];
            if (temp['material_code'] == material['material_code']) {
              product['req_qty'] = temp['batch_qty'];
            }
          }
          products[j] = product;
        }
        material['products'] = products;
        this.raw_materials[i] = material;
      } */

      let commons = [];
      for (let i = 0; i < this.raw_materials.length; i++) {
        let material = this.raw_materials[i];
        
        commons[commons.length] = material['material_code'];
      }
      let counts = {};
      for (const num of commons) {
        counts[num] = counts[num] ? counts[num] + 1 : 1;
      }

      let temp = [];
      let temp1 = [];
      let materialvalues = Object.keys(counts);
      for (const key of materialvalues) {
        if (counts[key] > 1) {
          temp[temp.length] = key;
        } else {
          temp1[temp1.length] = key;
        }
      }

      this.uncommons = [];
      this.commons = [];
      for (let i = 0; i < this.raw_materials.length; i++) {
        let material = this.raw_materials[i];

        for (const num of temp1) {
          console.log(material['material_code']);
          console.log(num);
          if (material['material_code'] == num) {
            this.uncommons[this.uncommons.length] = material;
          }
        }

        for (const num of temp) {
          if (material['material_code'] == num) {
            this.commons[this.commons.length] = material;
          }
        }
      }

      for (let i = 0; i < this.commons.length; i++) {
        let common = this.commons[i];

        let products = this.productList;
        for (let j = 0; j < products.length; j++) {
          let product = products[j];
          product['req_qty'] = 0;
          let materials = product['raw_materials'];
          for (let k = 0; k < materials.length; k++) {
            let temp = materials[k];
            if (temp['material_code'] == common['material_code']) {
              product['req_qty'] = +temp['batch_qty'];
              break;
            }
          }
          products[j] = product;
        }
        common['products'] = products;
        this.commons[i] = common;
      }

    });
  }

  download() {
    let temp = {};
    temp['commons'] = this.commons;
    temp['raw_materials'] = this.uncommons;
    temp['products'] = this.productList;
    console.log(temp);
    var form = document.createElement("form");
    form.target = "view";
    form.method = "POST";
    form.action = this.service.url + 'planning/raw.php?type=downloadPlan' + '&token=' + localStorage.getItem('token') + '&user_no=gmpdemo1';
    var params = temp;

    for (var i in params) {
        if (params.hasOwnProperty(i)) {
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = i;
          input.value = params[i];
          form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
    window.open('', 'view');
  }

}
