import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {
  affecting_product = 'yes';
  affecting_equipment = 'no';

  form: FormGroup;

  constructor(private service: DataAccessService, private router:Router, private formBuilder: FormBuilder) {

  }


  ngOnInit(): void {
  }

  saveUserForm(data) {
    const formData = new FormData();
    formData.append('related_to', data.value.related_to);
    formData.append('type', data.value.type);
    formData.append('incident_date', data.value.incident_date);
    formData.append('justification', data.value.justification);
    formData.append('cause', data.value.cause);
    formData.append('description', data.value.description);
    formData.append('affecting_product', data.value.affecting_product);
    formData.append('affecting_equipment', data.value.affecting_equipment);
    formData.append('product_name', data.value.product_name);
    formData.append('batch_no', data.value.batch_no);
    formData.append('mfg_date', data.value.mfg_date);
    formData.append('exp_date', data.value.exp_date);
    formData.append('equipment_name', data.value.equipment_name);

    this.service.post('qa/incident.php?type=saveIncident', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        data.resetForm();
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }

  close() {
    this.router.navigate(['/production/incidents']);
  }

}

