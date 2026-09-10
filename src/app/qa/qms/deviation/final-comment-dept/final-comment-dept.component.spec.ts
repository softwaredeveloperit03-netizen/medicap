import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinalCommentDeptComponent } from './final-comment-dept.component';

describe('FinalCommentDeptComponent', () => {
  let component: FinalCommentDeptComponent;
  let fixture: ComponentFixture<FinalCommentDeptComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinalCommentDeptComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinalCommentDeptComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
