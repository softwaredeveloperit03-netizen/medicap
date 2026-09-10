import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ManagerChecklistComponent } from './manager-checklist.component';

describe('ManagerChecklistComponent', () => {
  let component: ManagerChecklistComponent;
  let fixture: ComponentFixture<ManagerChecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ManagerChecklistComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ManagerChecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
