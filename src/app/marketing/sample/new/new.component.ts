import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  address;
  sample_address;
  requirement;
  mobile_no;
  country;
  unit_name;
  name_client;
  temp_name;
  temp_address;
  temp_country;
  temp_state;
  temp_Ocountry;
  temp_no;
  temp_email;
  stp;
  vendor_coa;
  enter_stp;
  units;
  clients;
  selectedFile1: File;
  selectedFile2: File;
  solvent_system;
  enter;
  products: any[] = [];
  product_name = '';
  product_code = '';
  quantity_required;
  unit = '';
  purpose;
  client_code;
  isSubmitting = false;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getClients();
    this.getUnits();
    this.getproductlist();
  }

  checkAddress(value) {
    if (value) {
      this.temp_name = this.name_client;
      this.temp_address = this.address;
    } else {
      this.temp_name = '';
      this.temp_address = '';
      this.temp_country = '';
      this.temp_state = '';
      this.temp_Ocountry = '';
      this.temp_no = '';
      this.temp_email = '';
    }
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    if (this.isSubmitting) {
      return;
    }
    this.isSubmitting = true;
    const temp = Object.assign({}, data.value);
    if (temp.requirement === 'existing product' && temp.product_code) {
      const selected = this.products.find((p) => p.product_code === temp.product_code);
      temp.product_name = selected ? selected.product_name : temp.product_code;
    }
    this.service.post('marketing/sample.php?type=saveSample', JSON.stringify(temp)).subscribe(response => {
      this.isSubmitting = false;
      if (response['status'] == 'success') {
        alert('Sample request sent to QA Head for issuing quantity');
        data.reset();
        this.router.navigate(['/marketing/sample/log']);
      } else {
        console.log('response', response);
        alert('Failed: An error occured');
      }
    }, () => {
      this.isSubmitting = false;
      alert('Failed: An error occured');
    });
  }

  
  onFileChanged1(event) {
    this.selectedFile1 = event.target.files[0];
  }
  onFileChanged2(event) {
    this.selectedFile2 = event.target.files[0];
  }

  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }
  getproductlist() {
    this.service.get('master/product.php?type=getProductsLog').subscribe({
      next: (response: any) => {
        const products = Array.isArray(response) ? response : [];
        if (products.length) {
          this.setFinishedProducts(products);
          return;
        }
        this.loadBrandProductsFallback();
      },
      error: () => this.loadBrandProductsFallback(),
    });
  }

  private loadBrandProductsFallback(): void {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe({
      next: (response: any) => {
        const products = Array.isArray(response) ? response : [];
        if (products.length) {
          this.setFinishedProducts(products);
          return;
        }
        this.loadApprovedProductsFallback();
      },
      error: () => this.loadApprovedProductsFallback(),
    });
  }

  private loadApprovedProductsFallback(): void {
    this.service.get('master/product.php?type=getProductApprovedProduct').subscribe({
      next: (response: any) => {
        this.setFinishedProducts(Array.isArray(response) ? response : []);
      },
      error: () => {
        this.products = [];
      },
    });
  }

  private normalizeProductCode(product: any): string {
    return String(product?.product_code || product?.product_code1 || '').trim();
  }

  private setFinishedProducts(products: any[]): void {
    const seen = new Set<string>();
    this.products = products
      .map((product) => ({
        ...product,
        product_code: this.normalizeProductCode(product),
      }))
      .filter((product) => {
        const name = String(product?.product_name || '').trim();
        if (!name || !product.product_code) {
          return false;
        }
        if (seen.has(product.product_code)) {
          return false;
        }
        seen.add(product.product_code);
        return true;
      })
      .map((product) => ({
        ...product,
        displayLabel: this.getProductLabel(product),
      }))
      .sort((a, b) => a.displayLabel.localeCompare(b.displayLabel));
  }

  getProductLabel(product: any): string {
    const name = (product?.product_name || '').toString().trim();
    const code = (product?.product_code || '').toString().trim();
    if (name && code) {
      return `${name} (${code})`;
    }
    return name || code || 'Unnamed Product';
  }

  onRequirementChange(): void {
    this.product_name = '';
    this.product_code = '';
  }

  onProductChange(): void {
    const selected = this.products.find((p) => p.product_code === this.product_code);
    this.product_name = selected ? selected.product_name : '';
  }

  getUnits() {
    this.service.get('common.php?type=getUnits_List').subscribe((response) => {
      this.units = Array.isArray(response) ? response : [];
    });
  }
}
