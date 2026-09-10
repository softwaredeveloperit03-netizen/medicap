import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InprocessFormulationComponent } from './inprocess-formulation.component';

describe('InprocessFormulationComponent', () => {
  let component: InprocessFormulationComponent;
  let fixture: ComponentFixture<InprocessFormulationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InprocessFormulationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InprocessFormulationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
