import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OcustomerComponent } from './ocustomer.component';

describe('OcustomerComponent', () => {
  let component: OcustomerComponent;
  let fixture: ComponentFixture<OcustomerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OcustomerComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OcustomerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
