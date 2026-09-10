import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { ReactiveFormsModule } from '@angular/forms';

@Component({
  selector: 'app-cleaning',
  templateUrl: './cleaning.component.html',
  styleUrls: ['./cleaning.component.css']
})
export class CleaningComponent implements OnInit {
  isView = false;
  isNew = false;
  selectedEntry;
  Gowns = [];
  GownsForm: FormGroup;
  list;

  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {
  }

  ngOnInit() {
    this.GownsForm = this.fb.group({
      po_number: ['', [Validators.required]],
      vendor_name: ['', [Validators.required]],
      quality_review: ['', [Validators.required]],
      gowns: this.fb.array([this.createGown()])
    });
    this.getGownsList();
    // this.addGown();
  }
  get gownsControls() {
    return (this.GownsForm.get('gowns') as FormArray).controls;
  }
  createGown(): FormGroup {
    return this.fb.group({
      size: ['', Validators.required],
      qty_received: ['', [Validators.required, Validators.min(1)]]
    });
  }
  addGown() {
    const gowns = this.GownsForm.get('gowns') as FormArray;
    gowns.push(this.fb.group({
      size: ['', Validators.required],
      qty_received: ['', [Validators.required, Validators.min(1)]]
    }));
  }

  removeGown(index: number): void {
    const gowns = this.GownsForm.get('gowns') as FormArray;
    gowns.removeAt(index);
  }

  deleteGowns(index) {
    this.Gowns.splice(index, 1);
  }

  viewEntry(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getGownsList() {
    this.service.get('admin.php?type=getGownsReceivedEntry').subscribe(response => {
      this.list = response;
    });
  }

  saveForm() {
    if (this.GownsForm.valid) {
      const formData = this.GownsForm.value;
      const formDataToSend = new FormData();
      formDataToSend.append('po_number', formData.po_number);
      formDataToSend.append('vendor_name', formData.vendor_name);
      formDataToSend.append('quality_review', formData.quality_review);
      formDataToSend.append('gowns', JSON.stringify(formData.gowns));

      this.service.post('admin.php?type=saveGownsReceivedEntry', formDataToSend).subscribe((response: any) => {
        const result = response;
        if (result.status === 'success') {
          this.getGownsList();
          this.GownsForm.reset();
          this.isNew = false;
          this.Gowns = [];
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
        }
      },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, HTTP status:' + error.status);
        }
      });
    } else {
      alert('Form is invalid. Please fill out all required fields.');
    }
  }
  close() {
    this.router.navigate(['/laundry-dashboard']);
  }
}
