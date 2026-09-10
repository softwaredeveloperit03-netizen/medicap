import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpinprocessFormulationComponent } from './spinprocess-formulation.component';

describe('SpinprocessFormulationComponent', () => {
  let component: SpinprocessFormulationComponent;
  let fixture: ComponentFixture<SpinprocessFormulationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpinprocessFormulationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpinprocessFormulationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
