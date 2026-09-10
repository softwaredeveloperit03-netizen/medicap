import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-edit-specification',
  templateUrl: './edit-specification.component.html',
  styleUrls: ['./edit-specification.component.css']
})
export class EditSpecificationComponent implements OnInit {

  reactiveForm: FormGroup;
  selectedSpec = [];
  selectedSpecification = [];
  constructor(private service: DataAccessService, private fb: FormBuilder) {

    this.reactiveForm = this.fb.group({
      change_related: ['', [ Validators.required ]],
      change_title: ['', [ Validators.required ]],
      existing_procedure: ['', [ Validators.required ]],
      proposed_change: ['', [ Validators.required ]],
      reason_for_changes: ['', [ Validators.required ]],
      product_name: ['', [ Validators.required ]],
      export: ['', [ Validators.required ]],
      domastic: ['', [ Validators.required ]],
      No: ['', [ Validators.required ]],
      description: ['', [ Validators.required ]]
    });
  }

  ngOnInit() {
  }

  saveForm(changecontrol) {
    this.service.post('changecontrol.php?type=saveform', JSON.stringify(changecontrol.value)).subscribe(response => {
      if (response['status'] === 'success') {
        changecontrol.resetForm();
        alert('Successfully send for Approval');
      } else {
        alert('An error has occurred, please try again');
      }
      }, (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
