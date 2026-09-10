import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-bill',
  templateUrl: './bill.component.html'
})
export class BillComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;
  file: File;
  file_bill: File;
  list;
  vendor;
  bill;
  vendors;
  vendor_name ='';
  vehicle_no ='';
  vehicle;
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getBillData();
    this.getVehiclelist();
  }


  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.bill = this.service.url + this.selectedEntry['file_bill'];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getBillData() {
    this.service.get('admin.php?type=getBillData').subscribe(response => {
      this.list = response;
    });
  }

  getVehiclelist() {
    this.service.get('admin.php?type=getVehiclelist').subscribe(response => {
      this.vendors = response;
    });
  }

  getVehicleNo() {
    this.service.get('admin.php?type=getVehicleById&selectedvendor_name=' + this.vendor_name).subscribe(response => {
      this.vehicle = response;
    });
  }

  onFileChange($event, name) {
    if (name == 'bill') {
      this.file_bill = $event.target.files[0];
    }
   }

   saveForm(data) {
      const formData = new FormData();
      formData.append('from_date', data.value.from_date);
      formData.append('to_date', data.value.to_date);
      formData.append('vendor_name', data.value.vendor_name);
      formData.append('vehicle_no', data.value.vehicle_no);
      formData.append('km', data.value.km);
      formData.append('file_bill', this.file_bill, this.file_bill.name);

      this.service.post('admin.php?type=AddTransporterBill', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getBillData();
          this.isNew = false;
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

  close() {
    this.router.navigate(['/transport-dashboard']);
  }

}

