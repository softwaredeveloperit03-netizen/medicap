import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeviationHodComponent } from './deviation-hod.component';

describe('DeviationHodComponent', () => {
  let component: DeviationHodComponent;
  let fixture: ComponentFixture<DeviationHodComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeviationHodComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeviationHodComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
