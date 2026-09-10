import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ReactiveFormsModule,FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {
isdata: any;

visibleform: FormGroup;
absorbance: FormGroup;
isView: any;
results: any;



  constructor(private router: Router,public formBuilder: FormBuilder) { }

  ngOnInit(): void {

  
  
  
  this.visibleform = this.formBuilder.group({
    id:['', [Validators.required]],
    Sr_no:['', [Validators.required]],
    m_no:['', [Validators.required]],
    Make:['', [Validators.required]],
    Location:['', [Validators.required]],
    due_date:['', [Validators.required]],
    Date:['', [Validators.required]],
  });

  // this.absorbance = this.formBuilder.group({
  //   id:['', [Validators.required]],
  //   Sr_no:['', [Validators.required]],
  //   m_no:['', [Validators.required]],
  //   Make:['', [Validators.required]],
  //   Location:['', [Validators.required]],
  //   due_date:['', [Validators.required]],
  //   Date:['', [Validators.required]],
  // });

  // this.form = this.formBuilder.group({
  //   id:['', [Validators.required]],
  //   Sr_no:['', [Validators.required]],
  //   m_no:['', [Validators.required]],
  //   Make:['', [Validators.required]],
  //   Location:['', [Validators.required]],
  //   due_date:['', [Validators.required]],
  //   Date:['', [Validators.required]],
  // });

    
  }

  isShown: boolean = false; // hidden by default

  toggleShow() {
    this.isShown = !this.isShown;
    }
    
}
