import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  selectedFile: any;
  selectedFile2: any;
  constructor(private service: DataAccessService, private router: Router) {}
  DevList = [];
  ngOnInit(): void {}
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.DevList[this.DevList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.DevList.splice(index, 1);
  }
  download() {
    this.service.open('');
  }
  onFileChanged2(event) {
    this.selectedFile = event.target.files[0];
  }
  onFileChanged3(event) {
    this.selectedFile = event.target.files[0];
  }

  save(data) {
    // if(!data.valid){
    //   alertify.error('Please Select Attachment')
    //   return;
    // }
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append(
        'supporing_doc',
        this.selectedFile,
        this.selectedFile.name
      );
    }
    if (this.selectedFile2 !== undefined) {
      uploadData.append(
        'sign_doc',
        this.selectedFile2,
        this.selectedFile2.name
      );
    }

    this.service
      .post('qa/all.php?type=saveDeviationOQDumyform', uploadData)
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
