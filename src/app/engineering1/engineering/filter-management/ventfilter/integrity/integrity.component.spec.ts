import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IntegrityComponent } from './integrity.component';

describe('IntegrityComponent', () => {
  let component: IntegrityComponent;
  let fixture: ComponentFixture<IntegrityComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IntegrityComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(IntegrityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
