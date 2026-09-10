import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  selectedFile1;
  selectedFile2;

  constructor(private service: DataAccessService, private route:Router) { }

  ngOnInit() {
  }


  onFileChanged1(event) {
    this.selectedFile1 = event.target.files[0];
  }
  onFileChanged2(event) {
    this.selectedFile2 = event.target.files[0];
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp= data.value;
    // temp['material_type'] = "Packing Material";

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile1 !== undefined) {
      uploadData.append('image', this.selectedFile1, this.selectedFile1.name);
    }

    if (this.selectedFile2 !== undefined) {
      uploadData.append('contract_agreement', this.selectedFile2, this.selectedFile2.name);
    }


    this.service.post('hr/physician.php?type=savePhysician', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.resetForm();
        this.route.navigate(['/hr/medical/physicians'])
      } else {
        alertify.error('Failed');
      }
    });
  }

}
