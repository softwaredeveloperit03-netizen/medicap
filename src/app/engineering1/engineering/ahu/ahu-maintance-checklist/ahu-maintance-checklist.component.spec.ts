import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AhuMaintanceChecklistComponent } from './ahu-maintance-checklist.component';

describe('AhuMaintanceChecklistComponent', () => {
  let component: AhuMaintanceChecklistComponent;
  let fixture: ComponentFixture<AhuMaintanceChecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AhuMaintanceChecklistComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AhuMaintanceChecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
