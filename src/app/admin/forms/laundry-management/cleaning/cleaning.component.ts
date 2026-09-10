import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

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
  GownsForm : FormGroup;
  list;

  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {

    this.GownsForm = this.fb.group({
      size: ['', [Validators.required]],
      qty_received: ['', [Validators.required]],
    });

   }

  ngOnInit() {
    this.getGownsList();
  }

  addGowns() {
    this.Gowns.push({
      size: this.GownsForm.value.size,
      qty_received: this.GownsForm.value.qty_received
    });
    this.GownsForm.reset();
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

   saveForm(data) {
      const formData = new FormData();

      formData.append('po_number', data.value.po_number);
      formData.append('vendor_name', data.value.vendor_name);
      formData.append('quality_review', data.value.quality_review);

      formData.append('gowns', JSON.stringify(this.Gowns));

      this.service.post('admin.php?type=saveGownsReceivedEntry', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.getGownsList();
          data.resetForm();
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
          alert('An error has occurred, http status:' + error.status);
        }
      });
    }

  close() {
    this.router.navigate(['/laundry-dashboard']);
  }

}



