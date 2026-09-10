import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-deptheadapp',
  templateUrl: './deptheadapp.component.html',
  styleUrls: ['./deptheadapp.component.css']
})
export class DeptheadappComponent implements OnInit {

  isView = false;
  results;
 
  selectedReport = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessDamages();
  }


  selectedFile:File;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }






  getInprocessDamages() {
    this.service.get('store/raw.php?type=getInprocessDamagesinQAHEAD').subscribe(response => {
      this.results = response;
    });
  }
  damages =[];
  view(index) {
    this.selectedReport = this.results[index];
    this.damages = this.selectedReport['damage_details']?.containers;
    this.isView = true;

    console.log(this.damages);
  }

  update(data,status) {


    if(!data.valid){
      alertify.error('All Field Required');
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();
 
    if (this.selectedFile !== undefined) {
      uploadData.append('damage_image', this.selectedFile, this.selectedFile.name);
    }

    uploadData.append('damage_details', JSON.stringify(this.damages));

    
    
    this.service.post('store/raw.php?type=updateDamageInspection&status=' + status + '&id=' + this.selectedReport['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getInprocessDamages();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
 

 
 
}
