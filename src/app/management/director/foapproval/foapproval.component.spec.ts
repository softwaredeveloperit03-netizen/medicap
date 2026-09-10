import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FoapprovalComponent } from './foapproval.component';

describe('FoapprovalComponent', () => {
  let component: FoapprovalComponent;
  let fixture: ComponentFixture<FoapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FoapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FoapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
