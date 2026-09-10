import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EquipInstFormComponent } from './equip-inst-form.component';

describe('EquipInstFormComponent', () => {
  let component: EquipInstFormComponent;
  let fixture: ComponentFixture<EquipInstFormComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EquipInstFormComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EquipInstFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
