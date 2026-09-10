import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PotentiometerComponent } from './potentiometer.component';

describe('PotentiometerComponent', () => {
  let component: PotentiometerComponent;
  let fixture: ComponentFixture<PotentiometerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PotentiometerComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PotentiometerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
