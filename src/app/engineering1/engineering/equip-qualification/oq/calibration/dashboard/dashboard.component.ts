import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  selectedFile: any;
  isview = false;
  selectedDev = [];
  results;
  constructor(private service: DataAccessService, private router: Router) {}
  CheckList: any[] = [];

  ngOnInit(): void {}

  addData(data) {
    // console.log('addData function is being called');
    // console.log('Data:', data.value);

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.CheckList[this.CheckList.length] = temp;
    data.resetForm();
  }



  delData(index) {
    this.CheckList.splice(index, 1);
  }

  download() {
    this.service.open('qa/all.php?type=savemanufacture_certificateform');
  }

  onFileChanged5(event) {
    this.selectedFile = event.target.files[0];
  }
  view(i) {
    this.selectedDev = this.results[i];
    this.isview = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('Please Select Attachment');
      return;
      
    }
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append(
        'uplode_doc',
        this.selectedFile,
        this.selectedFile.name
      );
    }
  
    this.service
      .post('qa/all.php?type=savemanufacture_certificateform', uploadData)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          // this.router.navigate(['/checklist']);
        } else {
          console.log(response);
          alert('Failed: An error occured, please try again!');
        }
      });
  }
}
