import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VendorquotationsComponent } from './vendorquotations.component';

describe('VendorquotationsComponent', () => {
  let component: VendorquotationsComponent;
  let fixture: ComponentFixture<VendorquotationsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VendorquotationsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(VendorquotationsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
