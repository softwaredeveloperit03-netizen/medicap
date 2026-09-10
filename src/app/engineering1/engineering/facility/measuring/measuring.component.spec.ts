import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MeasuringComponent } from './measuring.component';

describe('MeasuringComponent', () => {
  let component: MeasuringComponent;
  let fixture: ComponentFixture<MeasuringComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MeasuringComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MeasuringComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
