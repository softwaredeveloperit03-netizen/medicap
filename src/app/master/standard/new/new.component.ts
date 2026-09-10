import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  grades;
  formData: any = {
    material_type: 'Raw Material',
    material_subtype: '',
    material_name: '',
    standard_name: '',
    standard_category: 'Primary Standard',
    grade: '',
    standard: 'Primary Standard',
    analyte_marker: '',
    pharmacopeia_reference: '',
    cas_no: '',
    potency: '',
    purity: '',
    manufacturer: '',
    catalog_no: '',
    batch_no: '',
    storage_condition: '',
    valid_upto: '',
    remarks: ''
  };

  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
    });
  }

  saveStandardMaster(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const payload = { ...this.formData, ...data.value };
    this.service.post('qc/standard.php?type=saveStandard', JSON.stringify(payload)).subscribe(response => {
      if (response['status'] === 'success') {
        this.router.navigate(['/master/standard/approval'])
        alertify.success('Form has been saved successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }

}
