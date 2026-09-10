import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-final-report',
  templateUrl: './final-report.component.html',
  styleUrls: ['./final-report.component.css'],
})
export class FinalReportComponent implements OnInit {
  selectedFile: any;
  constructor(private service: DataAccessService, private router: Router) {}

  CheckList = [];
  ngOnInit(): void {}
  addData(data) {
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
  onFileChanged5(event) {
    this.selectedFile = event.target.files[0];
  }
  save(data) {
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append('sign', this.selectedFile, this.selectedFile.name);
    }

    this.service
      .post('qa/all.php?type=saveFinalReportform', uploadData)
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
