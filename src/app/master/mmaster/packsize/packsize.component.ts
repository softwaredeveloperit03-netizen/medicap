import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-packsize',
  templateUrl: './packsize.component.html',
  styleUrls: ['./packsize.component.css']
})
export class PacksizeComponent implements OnInit {

  constructor(public service: DataAccessService, private router: Router) {

   }

   pack_sizes;
   units;
   dosageForms = [];
   loading = false;

  ngOnInit(): void {
    this.getPackSize();
    this.getUnits();
    this.getDosageForms();
  }

  getDosageForms() {
    this.dosageForms = [];
    this.service.get('common.php?type=getDosages').subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      const seen = new Set<string>();
      this.dosageForms = rows.filter((row: any) => {
        const name = String(row?.dosage_form_type || '').trim();
        if (!name || seen.has(name.toLowerCase())) {
          return false;
        }
        seen.add(name.toLowerCase());
        return true;
      });
    });
  }

  getUnits() {
    this.units = []
    this.service.get('common.php?type=getUnits_List').subscribe(response => {
      this.units = response
    })
  }


  getPackSize() {
    this.loading = true;
    this.pack_sizes = []
    this.service.get('common.php?type=getPackSize').subscribe(response => {
      this.pack_sizes = response
      this.loading = false;
    }, () => {
      this.loading = false;
    });
  }

  private isDuplicateEntry(value: any): boolean {
    const dosageForm = String(value?.dosage_form || '').trim().toLowerCase();
    const packSizeType = String(value?.forWhat || '').trim().toLowerCase();
    const packSize = String(value?.pack_size || '').trim().toLowerCase();
    if (!dosageForm || !packSizeType || !packSize) {
      return false;
    }
    return (this.pack_sizes || []).some((row: any) =>
      String(row?.dosage_form || '').trim().toLowerCase() === dosageForm &&
      String(row?.forWhat || '').trim().toLowerCase() === packSizeType &&
      String(row?.pack_size || '').trim().toLowerCase() === packSize
    );
  }

  savePackSize(data) {
    if (!data.valid) {
      alertify.error('All required fields must be filled');
      return;
    }
    if (this.isDuplicateEntry(data.value)) {
      alertify.error('Duplicate pack size is not allowed for the same Dosage Form and Pack Size Type');
      return;
    }
    this.service.post('master/master.php?type=save_pack_size', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPackSize();
        alertify.success('Saved successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }

}
