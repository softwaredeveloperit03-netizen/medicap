import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
 


export class NewComponent implements OnInit {
  selectedFile: any;
  isUpload = 0;
  plant_id: any;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields("plant_id")


  }


  solution_type = '';
  strength_unit = 'M';
  normal_unit = 'N';






  saveVolumetricMaster(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
     
    this.service.post('qc/volumetric.php?type=saveVolumetricMaster', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/master/volumetric/log'])
        alertify.success('Volumetric Solution sent for approval successfully');
      } else {
        alertify.error('Duplicate Entry for Volumetric Solution Name');
      }
    });
  }

}
