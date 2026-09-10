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
  isview = false;
  selectedDev = [];
  results;
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {}

  onFileChanged5(event) {
    this.selectedFile = event.target.files[0];
  }
  view(i) {
    this.selectedDev = this.results[i];
    this.isview = true;
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
