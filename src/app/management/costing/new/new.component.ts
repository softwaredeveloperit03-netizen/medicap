import { HttpClient } from '@angular/common/http';
import { Component, OnInit, OnDestroy, ViewChild, AfterViewInit, HostListener } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { debounceTime, takeUntil } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { FormDraftService } from 'src/app/form-draft.service';
declare let alertify;


interface Material {
  material_code: string;
  unit?: string;
  batch_size?: number;
  rate?: number | null;
  rate_unit?: string;
  b_unit?: string;
  purchase_rate?: number;
  [key: string]: any; // Add other properties as needed
}   

const FORM_ID_DRAFT = 'management_costing_new';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit, AfterViewInit, OnDestroy {

  @ViewChild('newcosting') newcostingForm: NgForm;

  product_for = '';
  product_type = '';
  doller_value = 0;
  results;
  draftRestored = false;
  draftSavedAt: string | null = null;
  private destroy$ = new Subject<void>();
  private draftKey: string;

  selectedResult = [];

  selectedBatch = [];

  analytical_cost = 0;
  ccpc = 0;
  fright = 0;
  other_cost = 0;
  pack_cost=0;
  unit_cost=0;

  total = 0;
  per_unit = 0;
  per_pack = 0;
  costing_type='Estimate Costing';
  batch_cost=0;
  fright_cost=0;
  materials;
  batch_size='';
  product_code='';
  mfr;
  selectedMfr=[];
  pack_size;
  pack_size1: any;
  rate: Object;
  p_code: any;
  constructor(
    private service: DataAccessService,
    private router: Router,
    private http: HttpClient,
    private formDraft: FormDraftService
  ) {}

  ngOnInit() {
    this.draftKey = this.formDraft.getKey(
      FORM_ID_DRAFT,
      localStorage.getItem('emp_id') || localStorage.getItem('loger_id'),
      localStorage.getItem('plant_id') || undefined
    );
    this.getProducts();
    this.tryRestoreDraft();
  }

  ngAfterViewInit() {
    if (this.newcostingForm && this.newcostingForm.form) {
      this.newcostingForm.form.valueChanges
        .pipe(debounceTime(400), takeUntil(this.destroy$))
        .subscribe(() => this.saveDraft());
    }
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private tryRestoreDraft() {
    setTimeout(() => {
      const draft = this.formDraft.getDraft(this.draftKey);
      if (!draft || !draft.value) return;
      const v = draft.value as any;
      this.product_for = v.product_for ?? this.product_for;
      this.product_type = v.product_type ?? this.product_type;
      this.product_code = v.product_code ?? this.product_code;
      this.doller_value = v.doller_value ?? this.doller_value;
      this.pur_rate = v.pur_rate ?? this.pur_rate;
      this.batch_size = v.batch_size ?? this.batch_size;
      this.overages = v.overages ?? this.overages;
      this.analytical_cost = v.analytical_cost ?? this.analytical_cost;
      this.ccpc = v.ccpc ?? this.ccpc;
      this.fright = v.fright ?? this.fright;
      this.other_cost = v.other_cost ?? this.other_cost;
      this.currency = v.currency ?? this.currency;
      this.pack_size1 = v.pack_size1 ?? this.pack_size1;
      if (v.unit_formula_id) this.unit_formula_id = v.unit_formula_id;
      if (this.product_type) this.getProductsByDosage(this.product_type);
      if (this.product_code) {
        this.loadMfrAndRestore(this.product_code, v.mfr_no, v.unit_formula_id, v, draft.savedAt);
      } else {
        if (Array.isArray(v.rm_data)) this.rm_data = v.rm_data;
        if (Array.isArray(v.packingMaterials)) this.packingMaterials = v.packingMaterials;
        this.calcOver();
        if (this.rm_data && this.rm_data.length) this.calculateTotalAmt();
        this.calculate();
        this.draftRestored = true;
        this.draftSavedAt = draft.savedAt;
      }
    }, 300);
  }

  private loadMfrAndRestore(productCode: string, mfrNo?: string, unitFormulaId?: number, draftValue?: any, savedAt?: string) {
    this.service.get('master/materialtype.php?type=get_mfr&product_code=' + productCode).subscribe((res: any) => {
      this.mfr = res;
      this.p_code = productCode;
      const idx = (this.mfr || []).findIndex((m: any) => m.mfr_no === mfrNo || m.id === unitFormulaId);
      if (idx >= 0) this.get_mfrdetails(idx + 1);
      const applyDraftTables = () => {
        if (draftValue && Array.isArray(draftValue.rm_data)) this.rm_data = draftValue.rm_data;
        if (draftValue && Array.isArray(draftValue.packingMaterials)) this.packingMaterials = draftValue.packingMaterials;
        this.calcOver();
        if (this.rm_data && this.rm_data.length) this.calculateTotalAmt();
        this.calculate();
        this.draftRestored = true;
        this.draftSavedAt = savedAt || null;
      };
      setTimeout(applyDraftTables, 800);
    });
  }

  getDraftPayload(): object {
    return {
      product_for: this.product_for,
      product_type: this.product_type,
      product_code: this.product_code,
      doller_value: this.doller_value,
      pur_rate: this.pur_rate,
      batch_size: this.batch_size,
      overages: this.overages,
      analytical_cost: this.analytical_cost,
      ccpc: this.ccpc,
      fright: this.fright,
      other_cost: this.other_cost,
      currency: this.currency,
      pack_size1: this.pack_size1,
      unit_formula_id: this.unit_formula_id,
      mfr_no: this.selectedResult ? (this.selectedResult as any).mfr_no : undefined,
      rm_data: (this.rm_data || []).length ? this.rm_data : undefined,
      packingMaterials: (this.packingMaterials || []).length ? this.packingMaterials : undefined
    };
  }

  saveDraft(): void {
    const payload = this.getDraftPayload();
    if (!payload) return;
    this.formDraft.saveDraft(this.draftKey, payload, FORM_ID_DRAFT);
    this.draftSavedAt = new Date().toISOString();
  }

  discardDraft(): void {
    this.formDraft.clearDraft(this.draftKey);
    this.draftRestored = false;
    this.draftSavedAt = null;
    this.selectedResult = [];
    this.rm_data = [];
    this.packingMaterials = [];
    this.product_type = '';
    this.product_code = '';
    this.batch_size = '';
    this.overages = undefined;
    this.pur_rate = '';
    this.analytical_cost = 0;
    this.ccpc = 0;
    this.fright = 0;
    this.other_cost = 0;
    this.currency = undefined;
    this.pack_size1 = undefined;
    this.newcostingForm?.form?.reset();
    alertify.message('Draft discarded');
  }

  getProducts(){
    this.service.get('master/materialtype.php?type=GetProducts').subscribe(response => {
      this.products = response;
    });
  }
  rm_materials=[];
  rm_data:any=[];
 

  getMaterials(index) {
    index = index - 1;
    if (index !== -1) {
      let batches = this.selectedResult['batches'];
      this.selectedBatch = batches[index];
      this.calculate();
    }
  }
  products;
  getProductsByDosage(value) {
    this.product_type = value;
    this.service.get('master/materialtype.php?type=GetProducts').subscribe(response => {
      this.products = response;
    });
  }
  get_Mfr(event) {
    let value = event.target.value;
    let index = event.target.selectedIndex;

    this.service.get('master/materialtype.php?type=get_mfr&product_code=' + value).subscribe(response => {
      this.mfr = response;
    });


    this.p_code = value;


    this.pack_size = this.products[index]["pack_sizes"];
    console.log(this.pack_size.pack_size);
    console.log(index);
  }
  unit_formula_id;
    get_mfrdetails(index) {

       this.selectedResult = this.mfr[index-1];
       this.rm_data = this.selectedResult["raw_materials"];
       this.unit_formula_id=this.selectedResult['id']
       this.getPackingMaterialsByBfrNo();
    }



    pack_list=[]
countries1=[]
Pack_Size=[]
Pack_Sizess=[]
countriess;
    getPackingMaterialsByBfrNo() {
      this.pack_list = [];
      this.countries1 = [];
      this.service.get('planning/raw.php?type=get_packing_materials_by_mfr_id&unit_formula_id=' + this.unit_formula_id + '&market_type=' + 'Domestic' ).subscribe(response => {       
          this.countriess = response;
          // this.countries = response['pack_sizes'];
          // this.countries2 = response['pack_sizes']['pack_size1'];
          this.countries1 = response['packing_materials'];
          this.Pack_Size = response['pack_size1'];
          this.Pack_Sizess = this.Pack_Size
  
         });
        // this.bfr_batch_size = this.bfr_list[index-1]['batch_formula_weight'];
  
    }

  pur_rate='';


  avg_rate
  get_avg_rate(type){
    let temp={};

    temp['rmdate']= this.rm_data;
    temp['packingMaterials']= this.packingMaterials;
 
     this.service.post('master/materialtype.php?type=getRate&rateType='+ type ,JSON.stringify(temp)).subscribe(response => {
      this.avg_rate = response;     

      console.log('  this.calculateTotalAmtavg();    :>> ',  'true'   );
    });
  }
  Packing_total_amt_avg;
  Raw_total_amt_avg;
  
  getRate(){
    if(this.pur_rate!='manrate'){
      let temp={};

      temp['rmdate']= this.rm_data;
      temp['packingMaterials']= this.packingMaterials;
    console.log('temp :>> ', temp);
       this.service.post('master/materialtype.php?type=getRate&rateType='+this.pur_rate,JSON.stringify(temp)).subscribe(response => {
        this.rate = response;
        this.updateRmDataWithRates();
       
        // this.get_avg_rate('avgpurrate');
      });
   
  
      console.log(this.rate);
  
    }
    else{
      this.updateRmDataWithRates1();
          
    }


  }
  rm_date
  
  updateRmDataWithRates1(): void {
    // if (this.rate['rm_date']) {
      for (let i = 0; i < this.rm_data.length; i++) {
        // if (this.rate['rm_date'][i]) {
          // this.rm_data[i].rate = this.rate['rm_date'][i].rate;
          // this.rm_data[i].rate_unit = this.rate['rm_date'][i].rate_unit;
          // this.rm_data[i].batch_size=this.Final_overags_amt;
          if (this.rm_data[i].unit === 'mg' || this.rm_data[i].unit === 'Mg' || this.rm_data[i].unit === 'MG') {
            this.rm_data[i].b_unit = 'kg';
            this.rm_data[i].batch_size = this.Final_overags_amt / 1000000; // Convert mg to kg
          } else if (this.rm_data[i].unit === 'gm' || this.rm_data[i].unit === 'GM' || this.rm_data[i].unit === 'Gm') {
            this.rm_data[i].b_unit = 'kg';
            this.rm_data[i].batch_size = this.Final_overags_amt / 1000; // Convert gm to kg
          } else if (this.rm_data[i].unit === 'ml' || this.rm_data[i].unit === 'ML' || this.rm_data[i].unit === 'Ml'  ) {
            this.rm_data[i].b_unit = 'l';
            this.rm_data[i].batch_size = this.Final_overags_amt / 1000; // Convert ml to l
          } else {
            this.rm_data[i].batch_size = this.Final_overags_amt; // Default case
          }
          console.log('aaa',Number(this.rm_data[i].batch_size))
          console.log('bbb',Number(this.rm_data[i].rate))
          this.rm_data[i].purchase_rate=this.rm_data[i].batch_size * this.rm_data[i].rate
          this.rm_data[i].purchase_rate_avg=this.rm_data[i].batch_size*this.rm_data[i].avg_rate

        // }
      }
    // }
   
    // if (this.rate['packingMaterials']) {
      for (let i = 0; i < this.packingMaterials.length; i++) {
        // if (this.rate['packingMaterials'][i]) {
          // this.packingMaterials[i].rate = this.rate['packingMaterials'][i].rate;
          if (this.packingMaterials[i].unit === 'mg' || this.packingMaterials[i].unit === 'Mg' || this.packingMaterials[i].unit === 'MG') {
            this.packingMaterials[i].b_unit = 'kg';
            this.packingMaterials[i].batch_size = this.Final_overags_amt / 1000000; // Convert mg to kg
          } else if (this.packingMaterials[i].unit === 'gm' || this.packingMaterials[i].unit === 'GM' || this.packingMaterials[i].unit === 'Gm') {
            this.packingMaterials[i].b_unit = 'kg';
            this.packingMaterials[i].batch_size = this.Final_overags_amt / 1000; // Convert gm to kg
          } else if (this.packingMaterials[i].unit === 'ml' || this.packingMaterials[i].unit === 'ML' || this.packingMaterials[i].unit === 'Ml'  ) {
            this.packingMaterials[i].b_unit = 'l';
            this.packingMaterials[i].batch_size = this.Final_overags_amt / 1000; // Convert ml to l
          } else {
            this.packingMaterials[i].batch_size = this.Final_overags_amt; // Default case
          }
          this.packingMaterials[i].purchase_rate=Number(this.packingMaterials[i].batch_size)*Number(this.packingMaterials[i].rate)
          this.packingMaterials[i].purchase_rate_avg=Number(this.packingMaterials[i].batch_size)*Number(this.packingMaterials[i].avg_rate)

          // this.packingMaterials[i].batch_size=this.Final_overags_amt;
          // this.packingMaterials[i].purchase_rate=Number(this.packingMaterials[i].batch_size)*Number(this.packingMaterials[i].rate)

        // }
      // }
    }

    this.calculateTotalAmt();
    console.log('Updated rm_data:>> ', this.rm_data);
    console.log('Updated packingMaterials:>> ', this.packingMaterials);
  }
  updateRmDataWithRates(): void {
    if (this.rate['rm_date']) {
      for (let i = 0; i < this.rm_data.length; i++) {
        if (this.rate['rm_date'][i]) {
          this.rm_data[i].rate = this.rate['rm_date'][i].rate;
          this.rm_data[i].avg_rate = this.rate['rm_date'][i].avg_rate;
          this.rm_data[i].rate_unit = this.rate['rm_date'][i].rate_unit;
          // this.rm_data[i].batch_size=this.Final_overags_amt;
          if (this.rm_data[i].unit === 'mg' || this.rm_data[i].unit === 'Mg' || this.rm_data[i].unit === 'MG') {
            this.rm_data[i].b_unit = 'kg';
            this.rm_data[i].batch_size = this.Final_overags_amt / 1000000; // Convert mg to kg
          } else if (this.rm_data[i].unit === 'gm' || this.rm_data[i].unit === 'GM' || this.rm_data[i].unit === 'Gm') {
            this.rm_data[i].b_unit = 'kg';
            this.rm_data[i].batch_size = this.Final_overags_amt / 1000; // Convert gm to kg
          } else if (this.rm_data[i].unit === 'ml' || this.rm_data[i].unit === 'ML' || this.rm_data[i].unit === 'Ml'  ) {
            this.rm_data[i].b_unit = 'l';
            this.rm_data[i].batch_size = this.Final_overags_amt / 1000; // Convert ml to l
          } else {
            this.rm_data[i].batch_size = this.Final_overags_amt; // Default case
          }
          this.rm_data[i].purchase_rate=Number(this.rm_data[i].batch_size)*Number(this.rm_data[i].rate)
          this.rm_data[i].purchase_rate_avg=Number(this.rm_data[i].batch_size)*Number(this.rm_data[i].avg_rate)

        }
      }
    }
   
    if (this.rate['packingMaterials']) {
      for (let i = 0; i < this.packingMaterials.length; i++) {
        if (this.rate['packingMaterials'][i]) {
          this.packingMaterials[i].rate = this.rate['packingMaterials'][i].rate;
          this.packingMaterials[i].avg_rate = this.rate['packingMaterials'][i].avg_rate;
          if (this.packingMaterials[i].unit === 'mg' || this.packingMaterials[i].unit === 'Mg' || this.packingMaterials[i].unit === 'MG') {
            this.packingMaterials[i].b_unit = 'kg';
            this.packingMaterials[i].batch_size = this.Final_overags_amt / 1000000; // Convert mg to kg
          } else if (this.packingMaterials[i].unit === 'gm' || this.packingMaterials[i].unit === 'GM' || this.packingMaterials[i].unit === 'Gm') {
            this.packingMaterials[i].b_unit = 'kg';
            this.packingMaterials[i].batch_size = this.Final_overags_amt / 1000; // Convert gm to kg
          } else if (this.packingMaterials[i].unit === 'ml' || this.packingMaterials[i].unit === 'ML' || this.packingMaterials[i].unit === 'Ml'  ) {
            this.packingMaterials[i].b_unit = 'l';
            this.packingMaterials[i].batch_size = this.Final_overags_amt / 1000; // Convert ml to l
          } else {
            this.packingMaterials[i].batch_size = this.Final_overags_amt; // Default case
          }
          this.packingMaterials[i].purchase_rate=Number(this.packingMaterials[i].batch_size)*Number(this.packingMaterials[i].rate)
          this.packingMaterials[i].purchase_rate_avg=Number(this.packingMaterials[i].batch_size)*Number(this.packingMaterials[i].avg_rate)

          // this.packingMaterials[i].batch_size=this.Final_overags_amt;
          // this.packingMaterials[i].purchase_rate=Number(this.packingMaterials[i].batch_size)*Number(this.packingMaterials[i].rate)

        }
      }
    }
    this.calculateTotalAmt();

 
  }
 
  Packing_total_amt;
  Raw_total_amt;

  calculateTotalAmt(): void {
    this.Packing_total_amt = 0;
    this.Packing_total_amt_avg = 0; // Reset total_amt_avg before calculation
     // Reset total_amt before calculation
    for (const material of this.packingMaterials) {
        this.Packing_total_amt += Number(material.purchase_rate) || 0;
        this.Packing_total_amt_avg += Number(material.purchase_rate_avg) || 0;
    }
    console.log('Total Amount:>> ', this.Packing_total_amt);
    console.log('Total Amount avd:>> ', this.Packing_total_amt_avg);
    this.Raw_total_amt = 0; // Reset total_amt before calculation
    this.Raw_total_amt_avg = 0; // Reset total_amt_avg before calculation
    for (const material of this.rm_data) {
        this.Raw_total_amt += Number(material.purchase_rate) || 0;
        this.Raw_total_amt_avg += Number(material.purchase_rate_avg) || 0;
    }
    console.log('Total Amount:>> ', this.Raw_total_amt);
    console.log('Total Amount avg:>> ', this.Packing_total_amt_avg);
  }


  products1
 
  per_unit_avg;
  total_avg=0;
  per_pack_avg;
  calculate() {
    let total = 0;
    let total_avg = 0;
 
    total = + Number(this.Raw_total_amt) + Number(this.Packing_total_amt) + Number(this.analytical_cost) + Number(this.ccpc) + Number(this.fright) + Number(this.other_cost);
    total_avg= + Number(this.Raw_total_amt_avg) + Number(this.Packing_total_amt_avg) + Number(this.analytical_cost) + Number(this.ccpc) + Number(this.fright) + Number(this.other_cost);
 

    this.total = parseFloat(total.toFixed(2));
    this.total_avg = parseFloat(total_avg.toFixed(2));

    const batchSizeNum = Number(this.batch_size);
     this.per_unit = batchSizeNum > 0 ? parseFloat(((+this.total / batchSizeNum).toFixed(2))) : 0;
     this.per_unit_avg = batchSizeNum > 0 ? parseFloat(((+this.total_avg / batchSizeNum).toFixed(2))) : 0;
    this.per_pack = parseFloat(((+this.per_unit * 10).toFixed(2)));
    this.per_pack_avg = parseFloat(((+this.per_unit_avg * 10).toFixed(2)));
 
  }


  combinedData: Material[] = [];
  @HostListener('window:beforeunload')
  onBeforeUnload(): void {
    this.saveDraft();
  }

  saveCosting(form){
    const fieldsToInclude = ['material_code', 'unit', 'batch_size', 'rate', 'rate_unit', 'b_unit', 'purchase_rate'];

    const filteredRmData = (this.rm_data || []).map(item => {
      const newItem: Material = {
        material_code: item.material_Code,
        unit: item.unit,
        batch_size: item.batch_size,
        rate: item.rate,
        rate_unit: item.rate_unit,
        b_unit: item.b_unit,
        purchase_rate: item.purchase_rate,
        purchase_rate_avg: item.purchase_rate_avg,
      };
      return newItem;
    });

    const filteredPackingMaterials = (this.packingMaterials || []).map(item => {
      const newItem: Material = {
        material_code: item.material_code,
        unit: item.unit,
        batch_size: item.batch_size,
        rate: item.rate,
        rate_unit: item.rate_unit,
        b_unit: item.b_unit,
        purchase_rate: item.purchase_rate,
        purchase_rate_avg: item.purchase_rate_avg,
      };
      return newItem;
    });

    this.combinedData = filteredRmData.concat(filteredPackingMaterials);

    if (!form.valid) {
          alertify.error('All fields are required!');
          return;
    }


    const temp=form.value;
    temp['product_for'] = this.product_for;
    temp['product_code'] = this.product_code;
    temp['batch_size'] = this.batch_size;
    temp['doller_value']=this.doller_value;
    temp['materials']=this.combinedData;
    // temp['materials']=this.selectedBatch;
    temp['analytical_cost']=this.analytical_cost;
    temp['ccpc_cost']=this.ccpc;
    temp['fright_cost']=this.fright;
    temp['other_cost']=this.other_cost;
    temp['batch_cost']=this.total;
    temp['batch_cost_avg']=this.total_avg;
    temp['unit_cost']=this.per_unit;
    temp['unit_cost_avg']=this.per_unit_avg;
    temp['pack_cost']=this.per_pack;
    temp['pack_cost_avg']=this.per_pack_avg;
    temp['costing_type'] = this.costing_type;
    temp['currnecy'] = this.currnecy;
    temp['rates'] = this.rates;
   

    this.service.post('planning/costing.php?type=saveCosting1',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] == 'success'){
        this.formDraft.clearDraft(this.draftKey);
        this.draftRestored = false;
        this.draftSavedAt = null;
        alertify.success('Record Save Successfully');
        this.selectedBatch=[];
      }else(
        alertify.error('Error Occured')
      )
    });
   
  }

  currnecy;

  packingMaterials:any=[];

  Selected_Pack_Sizess=[]
  get_pack_size_by_mfrno(index){
          this.Selected_Pack_Sizess=this.Pack_Sizess[index-1];
          this.batch_size=this.Selected_Pack_Sizess['batch_size'];
          this.packingMaterials=this.Selected_Pack_Sizess['packing_materials']
          this.calcOver();
          if (this.pur_rate) {
            this.getRate();
          } else {
            this.calculateTotalAmt();
            this.calculate();
          }
          console.log('object :>> ', this.Selected_Pack_Sizess);
          console.log('object :>> ', this.batch_size);
  }

  overags_amt;
  overages;
  Final_overags_amt;
  calcOver(){
this.overags_amt=(Number(this.batch_size)*Number(this.overages))/100;
this.Final_overags_amt=Number(this.batch_size)+Number(this.overags_amt)
console.log('this.overages_amt :>> ', this.overags_amt);

  }

  conversionRates = {
    USD: 84, // Example conversion rate from INR to USD
    EUR: 91, // Example conversion rate from INR to EUR
    GBP: 91 // Example conversion rate from INR to GBP
  };
  
  // convertedAmounts = {
  //   USD: 0,
  //   EUR: 0,
  //   GBP: 0
  // };
  convertedAmounts=0;
  convertedAmounts_avg;
  currency;
  rates;
  calc_Currency(value){

    this.http.get('https://open.er-api.com/v6/latest/'+this.currency).subscribe((data: any) => {
      this.currency_rate = data.rates;
 this.rates=this.currency_rate['INR']


 if(value=='USD'){
  this.convertedAmounts = this.total / this.rates;
  this.convertedAmounts_avg = this.total_avg / this.rates;
} else if(value=='EUR'){
  this.convertedAmounts = this.total / this.rates;
  this.convertedAmounts_avg = this.total_avg / this.rates;
  
} else if(value=='GBP'){
  this.convertedAmounts = this.total / this.rates;
  this.convertedAmounts_avg = this.total_avg / this.rates;

}else{
  this.convertedAmounts=this.total
  this.convertedAmounts_avg=this.total_avg
}
const batchSizeNum = Number(this.batch_size);
this.per_unit = batchSizeNum > 0 ? parseFloat(((this.convertedAmounts / batchSizeNum).toFixed(2))) : 0;
this.per_unit_avg = batchSizeNum > 0 ? parseFloat(((this.convertedAmounts_avg / batchSizeNum).toFixed(2))) : 0;
this.per_pack = parseFloat(((this.per_unit * 10).toFixed(2)));
this.per_pack_avg = parseFloat(((this.per_unit_avg * 10).toFixed(2)));

 
      console.log('this.currency_rate1111 :>> ', this.currency_rate['INR']);
    },
    (error) => {
      console.error('Error fetching USD exchange rate:', error);
      this.currency_rate = 1;  
    }
  );
    console.log('this.currency_rate :>> ', this.currency_rate);
   
      // this.convertedAmounts.USD = this.total_amt * this.conversionRates.USD;
      // this.convertedAmounts.EUR = this.total_amt * this.conversionRates.EUR;
      // this.convertedAmounts.GBP = this.total_amt * this.conversionRates.GBP;
      // console.log('Converted Amounts:>> ', this.convertedAmounts);
  
  }


  currency_rate
  lancel(){
    this.http.get('https://open.er-api.com/v6/latest/'+this.currency).subscribe((data: any) => {
        this.currency_rate = data.rates;
   this.rates=this.currency_rate['INR']




   
        console.log('this.currency_rate1111 :>> ', this.currency_rate['INR']);
      },
      (error) => {
        console.error('Error fetching USD exchange rate:', error);
        this.currency_rate = 1;  
      }
    );
  }


  cal_rates(index: number, value: number) {
    if (this.rm_data[index]) {
      this.rm_data[index].purchase_rate = Number(this.rm_data[index].batch_size) * Number(value);
      const avgRate = Number(this.rm_data[index].avg_rate) || Number(value);
      this.rm_data[index].purchase_rate_avg = Number(this.rm_data[index].batch_size) * avgRate;
    }
    this.calculateTotalAmt();
    this.calculate();
  }
  cal_rates1(index: number, value: number) {
    if (this.packingMaterials[index]) {
      this.packingMaterials[index].purchase_rate = Number(this.packingMaterials[index].batch_size) * Number(value);
      const avgRate = Number(this.packingMaterials[index].avg_rate) || Number(value);
      this.packingMaterials[index].purchase_rate_avg = Number(this.packingMaterials[index].batch_size) * avgRate;
    }
    this.calculateTotalAmt();
    this.calculate();

  }
  


}
