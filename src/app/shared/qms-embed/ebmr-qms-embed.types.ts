/** Context passed from eBMR execution into QMS initiation forms (popup embed). */
export interface EbmrQmsEmbedContext {
  batch_id: number;
  batch_no?: string;
  batch_step_id?: number;
  stage_seq?: number;
  stage_name?: string;
  step_name?: string;
  product_code?: string;
  product_name?: string;
  profile_code?: string;
}
