import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-market-complaint',
  templateUrl: './market-complaint.component.html',
  styleUrls: ['./market-complaint.component.css'],
  providers: [DatePipe]
})
export class MarketComplaintComponent implements OnInit {
  formopen = false;
  complaintlist = [];
  clientform;
  dispatchedProducts;
  clientlist;
  selectedClient;
  selectedProduct;

  constructor(private service: DataAccessService, private datePipe: DatePipe) { }

  ngOnInit() {
    this.getcomplaintlist();
    this.getDispatchProducts();
    this.getclientlist();
  }
  getcomplaintlist() {
    this.service.get('marketing.php?type=getComplaintlist').subscribe((response: any) => {
      this.complaintlist = response;
    });
  }

  getDispatchProducts() {

    let date = new Date();
    let newdate = this.datePipe.transform(date, 'yyyy-MM-dd');
    console.log(newdate);
    this.service.get('dispatch.php?type=getDispatchProductsByDate').subscribe(response => {
      this.dispatchedProducts = JSON.parse(JSON.stringify(response));
      this.dispatchedProducts.forEach(element => {
        if(element.exp_date < newdate) {
           element.expiry = 'true';
        } else {
           element.expiry = 'false';
        }
      });
    });
  }

  onClientChange(index) {
    this.selectedClient = this.clientlist[index];
  }

  onProductChange(index) {
    this.selectedProduct = this.dispatchedProducts[index];
  }

  getclientlist() {
    this.service.get('client.php?type=getclientlist').subscribe((response : any) => {
      this.clientlist = response;
    })
  }

  submit(data) {
      const formData = new FormData();

      formData.append('product_name', this.selectedProduct.product_name);
      formData.append('batch_no', this.selectedProduct.batch_no);
      formData.append('mfg_date', this.selectedProduct.mfg_date);
      formData.append('exp_date', this.selectedProduct.exp_date);
      formData.append('dispatch_date', this.selectedProduct.entry_date);

      formData.append('received_from', this.selectedClient.company);
      formData.append('received_through', data.value.received_through);
      formData.append('deatils', data.value.deatils);

      formData.append('product', data.value.product);
      formData.append('packing', data.value.packing);

      formData.append('country', this.selectedClient.country);
      formData.append('contact_no', this.selectedClient.phone);

      this.service.post('marketing.php?type=addComplaint', formData).subscribe(response => {
        if (response['status'] === 'success') {
          alert('Record Inserted Successfully');
          this.selectedClient = [];
          this.selectedProduct = [];
          this.getcomplaintlist();
          this.formopen = false;
        } else {
          alert('Please try Again');
        }
      });
    }

  addclientbtn() {
    this.formopen = true;
  }
  closeclientbtn() {
    this.formopen = false;
  }

}
