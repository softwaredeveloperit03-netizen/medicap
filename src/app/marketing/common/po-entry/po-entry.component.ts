import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-po-entry',
  templateUrl: './po-entry.component.html',
  styleUrls: ['./po-entry.component.css']
})
export class PoEntryComponent implements OnInit {
  formopen = false;
  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  product_name = '';
  packing_config = '';
  configurations = [];
  // tslint:disable-next-line: variable-name
  company = '';
  products = [];
  file: File;
  // tslint:disable-next-line: variable-name
  enquiry_for = '';
  list;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getclientlist();
    this.getPOlist();
  }

  addProduct() {
    this.products[this.products.length] = this.product_name;
    this.product_name = '';
  }

  deleteProduct(index) {
    this.products.splice(index, 1);
  }

  addConfig() {
    this.configurations[this.configurations.length] = this.packing_config;
    this.packing_config = '';
  }

  deleteConfig(index) {
    this.configurations.splice(index, 1);
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getclientlist() {
    this.service.get('client.php?type=getclientlist').subscribe(response => {
      this.clients = response;
    });
  }

  getPOlist() {
    this.service.get('client.php?type=getPOlist').subscribe(response => {
      this.list = response;
    });
  }

  onFileChange($event) {
    this.file = $event.target.files[0];
   }

  saveForm(data) {
      const formData = new FormData();
      formData.append('company', data.value.company);
      formData.append('country', data.value.country);
      formData.append('place', data.value.place);
      formData.append('po_no', data.value.po_no);
      formData.append('products', this.products.toString());
      formData.append('packing_config', this.configurations.toString());
      formData.append('file', this.file, this.file.name);

      this.service.post('client.php?type=savePOEntry', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getPOlist();
          this.isNew = false;
          this.products = [];
          this.configurations = [];
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      });
  }


  addclientbtn() {
    this.formopen = true;
  }
  closeclientbtn() {
    this.formopen = false;
  }

  close() {
    this.router.navigate(['/dashboard']);
  }

}
