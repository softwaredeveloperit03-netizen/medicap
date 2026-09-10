import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PhMeterComponent } from './ph-meter.component';

describe('PhMeterComponent', () => {
  let component: PhMeterComponent;
  let fixture: ComponentFixture<PhMeterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PhMeterComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PhMeterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
