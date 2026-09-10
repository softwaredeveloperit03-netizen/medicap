import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-ftir',
  templateUrl: './ftir.component.html',
  styleUrls: ['./ftir.component.css']
})
export class FtirComponent implements OnInit {
  isNew= false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qc/calibration/bulkdensity.php?type=saveBulkDensity', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

}
