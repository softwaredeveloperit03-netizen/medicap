import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { SpecificationFormCustomisationService } from 'src/app/qa/soft-restriction/specification-form-customisation/specification-form-customisation.service';
import { SpecificationScope } from 'src/app/qa/soft-restriction/specification-form-customisation/specification-form-customisation.constants';
   declare let alertify;

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
})
export class HomeComponent implements OnInit {
    plant_id = localStorage.getItem('plant_id');
    isView = false;

    fieldLabelMap: { [key: string]: string } = {};
    dynamicFieldRows: Array<{ key: string; label: string; value: any }> = [];

    constructor(
      private service:DataAccessService,
      private specCustomisationService: SpecificationFormCustomisationService
    ) {}

    ngOnInit(): void {
      this.plant_id = localStorage.getItem('plant_id');
      this.loadSpecLayout();
      this.getApprovedSPecificationByTypeAndStatus();
    }

    private currentScope(): SpecificationScope {
      if (this.spec_type === 'Packing Material') {
        return 'Packing Material';
      }
      if (this.spec_type === 'Finish Product') {
        return 'Finish Product';
      }
      return 'Raw Material';
    }

    private loadSpecLayout(): void {
      this.specCustomisationService.getActiveLayout(this.currentScope()).subscribe({
        next: (res: any) => {
          const rows = Array.isArray(res?.fields) ? res.fields : [];
          const map: { [key: string]: string } = {};
          rows.forEach((r: any) => {
            const key = String(r?.field_key || '');
            if (key) {
              map[key] = String(r?.field_label || key);
            }
          });
          this.fieldLabelMap = map;
          this.rebuildDynamicRows();
        },
        error: () => {
          this.fieldLabelMap = {};
          this.rebuildDynamicRows();
        }
      });
    }

    private rebuildDynamicRows(): void {
      const raw = this.selectedResult?.dynamic_fields_json;
      let parsed: any = raw;
      if (typeof raw === 'string') {
        try {
          parsed = JSON.parse(raw);
        } catch {
          parsed = {};
        }
      }
      if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
        parsed = {};
      }
      this.dynamicFieldRows = Object.keys(parsed).map((k) => ({
        key: k,
        label: this.fieldLabelMap[k] || k,
        value: parsed[k]
      }));
    }

    results;
    spec_type = 'Raw Material';
    getApprovedSPecificationByTypeAndStatus(){
      this.service.get('qc/specification/raw.php?type=getApprovedSPecificationByTypeAndStatus&status=Approved&spec_type=Raw Material').subscribe(response => {
        this.results = response;
      });
    }

    approvalRemark = '';
    updateSpecification(status) {

      if(this.approvalRemark == ''){
        alertify.error('Please Add Remark.....');
        return;
      }

      let temp = {};
      temp['status'] = status;
      temp['id'] = this.selectedResult['id'];
      temp['approvalRemark'] = this.approvalRemark;

      this.service.post('qc/specification/raw.php?type=approveSpecification',JSON.stringify(temp)).subscribe(response => {
        if(response['status'] == 'success'){
          alertify.success('Specification '+status+' Successfully');
          this.getApprovedSPecificationByTypeAndStatus();
          this.isView = false;
        }else{
          alertify.error('Failed: An error occured, please try again!');
        }

      });

    }

    selectedResult: any = null;
    view(data) {
      this.selectedResult = data;
      this.isView = true;
      this.rebuildDynamicRows();
    }




  searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }



  isShow1 = true;
  toggleShow1(){
    this.isShow1 = !this.isShow1;
  }


  }
