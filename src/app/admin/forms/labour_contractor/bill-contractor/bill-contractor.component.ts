import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-bill-contractor',
  templateUrl: './bill-contractor.component.html'
})
export class BillContractorComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;
  file: File;
  file_bill1: File;
  list;
  bill1;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getContaractorBillData();
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.bill1 = this.service.url + this.selectedEntry['file_bill1'];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getContaractorBillData() {
    this.service.get('admin.php?type=getContaractorBillData').subscribe(response => {
      this.list = response;
    });
  }

  onFileChange($event, name) {
    if (name == 'bill1') {
      this.file_bill1 = $event.target.files[0];
    }
   }

   saveForm(data) {
      const formData = new FormData();
      formData.append('month', data.value.month);
      formData.append('labours_no', data.value.labours_no);
      formData.append('gents', data.value.gents);
      formData.append('gents_per_wages', data.value.gents_per_wages);
      formData.append('operators', data.value.operators);
      formData.append('operators_per_wages', data.value.operators_per_wages);
      formData.append('womens', data.value.womens);
      formData.append('womens_per_wages', data.value.womens_per_wages);
      formData.append('file_bill1', this.file_bill1, this.file_bill1.name);

      this.service.post('admin.php?type=AddContaractorBill', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getContaractorBillData();
          this.isNew = false;
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
    this.router.navigate(['/labour-dashboard']);
  }

}

