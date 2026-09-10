import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-vehicle',
  templateUrl: './vehicle.component.html'
})
export class VehicleComponent implements OnInit {

  isView = false;
  isNew = false;
  vendor_name ='';
  vendors;
  selectedEntry;
  list;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getVendorlist();
    this.getVehiclelist();
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank')
  }
  getVendorlist() {
    this.service.get('admin.php?type=getTransportorslist').subscribe(response => {
      this.vendors = response;
    });
  }

  getVehiclelist() {
    this.service.get('admin.php?type=getVehiclelist').subscribe(response => {
      this.list = response;
    });
  }
   saveForm(data) {
      const formData = new FormData();

      formData.append('vehicle_name', data.value.vehicle_name);
      formData.append('vehicle_model', data.value.vehicle_model);

      formData.append('vehicle_no', data.value.vehicle_no);
      formData.append('fuel_type', data.value.fuel_type);
      formData.append('capacity', data.value.capacity);
      formData.append('goods', data.value.goods);

      formData.append('passenger', data.value.passenger);
      formData.append('vendor_name', data.value.vendor_name);
      formData.append('phone_no', data.value.phone_no);

      this.service.post('admin.php?type=AddVehicleDetails', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getVehiclelist();
          this.isNew = false;
          alert('Successfully Saved');
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

