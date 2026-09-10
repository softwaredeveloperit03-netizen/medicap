import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BillContractorComponent } from './bill-contractor.component';

describe('BillContractorComponent', () => {
  let component: BillContractorComponent;
  let fixture: ComponentFixture<BillContractorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BillContractorComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BillContractorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
