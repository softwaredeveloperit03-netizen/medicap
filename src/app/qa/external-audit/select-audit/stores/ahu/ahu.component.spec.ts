import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AhuComponent } from './ahu.component';

describe('AhuComponent', () => {
  let component: AhuComponent;
  let fixture: ComponentFixture<AhuComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AhuComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AhuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
