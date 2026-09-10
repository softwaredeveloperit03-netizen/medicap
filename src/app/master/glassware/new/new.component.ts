import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'gw-app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
    plant_id: any;
    selectedFile2: File;
  
constructor(private service: DataAccessService,private router:Router) { }
gst;
  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields("plant_id")
    this.service.observableGst.subscribe(response => {
      this.gst = response;
    });
  }
  selectedFile: File;
  isUpload=0;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUpload = 1;
  }


  saveGlassware(data) {
    
    if(!data.valid) {
      alertify.error('All Fiedls are Required..');
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile !== undefined) {
      uploadData.append('coa', this.selectedFile, this.selectedFile.name);
    }
    this.service.post('qc/glassware.php?type=saveGlassware', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted successfully');
        data.resetForm();
        this.router.navigate(['/master/glassware']);
      } else {
        alertify.error("Failed: dupilcate entry for Glassware Name");
      }
    });
  }

}
