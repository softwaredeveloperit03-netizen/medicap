import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare var swal: any;

@Component({
  selector: 'app-plant-count',
  templateUrl: './plant-count.component.html',
  styleUrls: ['./plant-count.component.css']
})
export class PlantCountComponent implements OnInit {
  isNew = false;
  entries;
  selectedEntry;
  isView = false;

  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
    this.getPlantDetails();
  }

  viewEntry(index) {
    this.selectedEntry = this.entries[index];
    this.isView = true;
  }

  saveForm(data) {
    const formData = new FormData();

    formData.append('plant_name', data.value.plant_name);
    formData.append('count', data.value.count);
    formData.append('plantation_date', data.value.plantation_date);
    formData.append('plant_age', data.value.plant_age);

    this.service.post('admin.php?type=savePlantCount', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        data.resetForm();
        this.getPlantDetails();
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

  getPlantDetails() {
    this.service.get('admin.php?type=getPlantCount').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/admin/gardev']);
  }

}
