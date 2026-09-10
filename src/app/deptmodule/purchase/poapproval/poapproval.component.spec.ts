import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PoapprovalComponent } from './poapproval.component';

describe('PoapprovalComponent', () => {
  let component: PoapprovalComponent;
  let fixture: ComponentFixture<PoapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PoapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PoapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
